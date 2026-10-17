<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\DisciplinaryCase;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DisciplinaryCaseTest extends TestCase
{
    use RefreshDatabase;

    private Branch $hq;

    private Branch $ormoc;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hq = Branch::create(['name' => 'Tacloban HQ', 'code' => 'TAC']);
        $this->ormoc = Branch::create(['name' => 'Ormoc Site', 'code' => 'ORM']);
    }

    private function user(string $role, string $email, ?Branch $branch = null): User
    {
        return User::create([
            'name' => ucfirst($role), 'email' => $email, 'password' => 'password',
            'role' => $role, 'is_active' => true, 'branch_id' => $branch?->id,
        ]);
    }

    private function employee(string $no, Branch $branch, ?User $user = null): Employee
    {
        return Employee::create([
            'employee_no' => $no, 'first_name' => 'Juan', 'last_name' => $no,
            'branch_id' => $branch->id, 'user_id' => $user?->id, 'is_active' => true,
        ]);
    }

    public function test_hr_can_file_a_case(): void
    {
        $hr = $this->user(User::ROLE_HR, 'hr@test.local');
        $employee = $this->employee('E-001', $this->hq);

        $this->actingAs($hr)->post('/cases', [
            'employee_id' => $employee->id,
            'type' => DisciplinaryCase::TYPE_NTE,
            'severity' => 'high',
            'incident_date' => '2026-06-01',
            'subject' => 'Late for three consecutive shifts',
            'details' => 'Escalated by the team lead.',
            'response_due_on' => '2026-06-05',
        ])->assertRedirect();

        $case = DisciplinaryCase::firstOrFail();

        $this->assertSame($employee->id, $case->employee_id);
        $this->assertSame($this->hq->id, $case->branch_id);
        $this->assertSame($hr->id, $case->filed_by);
        $this->assertSame(DisciplinaryCase::STATUS_AWAITING, $case->status);
        $this->assertDatabaseCount('disciplinary_case_actions', 1);
    }

    public function test_a_manager_cannot_file_a_case_for_another_branch(): void
    {
        $manager = $this->user(User::ROLE_MANAGER, 'mgr@test.local', $this->hq);
        $outsider = $this->employee('E-002', $this->ormoc);

        $this->actingAs($manager)->post('/cases', [
            'employee_id' => $outsider->id,
            'type' => DisciplinaryCase::TYPE_INCIDENT,
            'severity' => 'low',
            'subject' => 'Test',
        ])->assertForbidden();

        $this->assertDatabaseCount('disciplinary_cases', 0);
    }

    public function test_a_manager_only_sees_cases_in_their_branch(): void
    {
        $manager = $this->user(User::ROLE_MANAGER, 'mgr@test.local', $this->hq);
        $mine = $this->employee('E-001', $this->hq);
        $theirs = $this->employee('E-002', $this->ormoc);

        $ownCase = DisciplinaryCase::create(['employee_id' => $mine->id, 'branch_id' => $this->hq->id, 'type' => 'incident', 'subject' => 'Visible case']);
        $otherCase = DisciplinaryCase::create(['employee_id' => $theirs->id, 'branch_id' => $this->ormoc->id, 'type' => 'incident', 'subject' => 'Hidden case']);

        $this->actingAs($manager)->get('/cases')->assertOk()->assertSee('Visible case')->assertDontSee('Hidden case');
        $this->actingAs($manager)->get('/cases/'.$otherCase->id)->assertForbidden();
        $this->actingAs($manager)->get('/cases/'.$ownCase->id)->assertOk();
    }

    public function test_the_employee_can_view_and_respond_to_their_own_case(): void
    {
        $employeeUser = $this->user(User::ROLE_EMPLOYEE, 'emp@test.local');
        $employee = $this->employee('E-001', $this->hq, $employeeUser);
        $hr = $this->user(User::ROLE_HR, 'hr@test.local');

        $this->actingAs($hr)->post('/cases', [
            'employee_id' => $employee->id,
            'type' => DisciplinaryCase::TYPE_NTE,
            'severity' => 'medium',
            'subject' => 'Absence without notice',
        ]);

        $case = DisciplinaryCase::firstOrFail();

        $this->actingAs($employeeUser)->get('/me/cases')->assertOk()->assertSee('Absence without notice');

        $this->actingAs($employeeUser)->post('/me/cases/'.$case->id.'/respond', [
            'body' => 'I was hospitalised and my mother called the team lead.',
        ])->assertRedirect();

        $this->assertSame(DisciplinaryCase::STATUS_OPEN, $case->fresh()->status);
        $this->assertDatabaseHas('disciplinary_case_actions', [
            'disciplinary_case_id' => $case->id,
            'type' => 'response',
        ]);
    }

    public function test_an_employee_cannot_open_another_employees_case(): void
    {
        $employeeUser = $this->user(User::ROLE_EMPLOYEE, 'emp@test.local');
        $this->employee('E-001', $this->hq, $employeeUser);
        $colleague = $this->employee('E-002', $this->hq);

        $case = DisciplinaryCase::create(['employee_id' => $colleague->id, 'branch_id' => $this->hq->id, 'type' => 'incident', 'subject' => 'Not mine']);

        $this->actingAs($employeeUser)->get('/me/cases/'.$case->id)->assertNotFound();
    }

    public function test_a_decision_closes_the_case_and_notifies_the_employee(): void
    {
        $employeeUser = $this->user(User::ROLE_EMPLOYEE, 'emp@test.local');
        $employee = $this->employee('E-001', $this->hq, $employeeUser);
        $hr = $this->user(User::ROLE_HR, 'hr@test.local');

        $case = DisciplinaryCase::create([
            'employee_id' => $employee->id, 'branch_id' => $this->hq->id, 'filed_by' => $hr->id,
            'type' => DisciplinaryCase::TYPE_NTE, 'severity' => 'high', 'status' => DisciplinaryCase::STATUS_AWAITING,
            'subject' => 'Absence without notice',
        ]);

        $this->actingAs($hr)->post('/cases/'.$case->id.'/decide', [
            'status' => DisciplinaryCase::STATUS_RESOLVED,
            'decision' => 'Explanation not accepted.',
            'sanction' => 'Written warning',
        ])->assertRedirect();

        $case->refresh();

        $this->assertSame(DisciplinaryCase::STATUS_RESOLVED, $case->status);
        $this->assertSame('Written warning', $case->sanction);
        $this->assertSame($hr->id, $case->decided_by);
        $this->assertSame(1, $employeeUser->fresh()->unreadNotifications()->count());
    }

    public function test_a_closed_case_cannot_be_answered_by_the_employee(): void
    {
        $employeeUser = $this->user(User::ROLE_EMPLOYEE, 'emp@test.local');
        $employee = $this->employee('E-001', $this->hq, $employeeUser);

        $case = DisciplinaryCase::create([
            'employee_id' => $employee->id, 'branch_id' => $this->hq->id,
            'type' => DisciplinaryCase::TYPE_MEMO, 'severity' => 'low', 'status' => DisciplinaryCase::STATUS_CLOSED,
            'subject' => 'Reminder on dress code',
        ]);

        $this->actingAs($employeeUser)->post('/me/cases/'.$case->id.'/respond', ['body' => 'Noted.'])->assertForbidden();
    }
}
