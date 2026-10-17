<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamScopeTest extends TestCase
{
    use RefreshDatabase;

    private Branch $hq;

    private Branch $ormoc;

    private User $managerHq;

    private User $managerOrmoc;

    private Employee $employeeHq;

    private Employee $employeeOrmoc;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hq = Branch::create(['name' => 'HQ', 'code' => 'HQ']);
        $this->ormoc = Branch::create(['name' => 'Ormoc', 'code' => 'ORMOC']);

        $this->managerHq = $this->user(User::ROLE_MANAGER, 'mgr-hq@test.local', $this->hq->id);
        $this->managerOrmoc = $this->user(User::ROLE_MANAGER, 'mgr-ormoc@test.local', $this->ormoc->id);

        $this->employeeHq = $this->employee('E-HQ', 'HQ Staff', $this->hq->id);
        $this->employeeOrmoc = $this->employee('E-ORMOC', 'Ormoc Staff', $this->ormoc->id);
    }

    private function user(string $role, string $email, ?int $branchId): User
    {
        return User::create([
            'name' => ucfirst($role), 'email' => $email,
            'password' => 'password', 'role' => $role, 'branch_id' => $branchId, 'is_active' => true,
        ]);
    }

    private function employee(string $no, string $name, int $branchId): Employee
    {
        [$first, $last] = explode(' ', $name);

        return Employee::create([
            'employee_no' => $no, 'first_name' => $first, 'last_name' => $last,
            'branch_id' => $branchId, 'is_active' => true,
        ]);
    }

    private function attendance(Employee $employee): Attendance
    {
        return Attendance::create([
            'employee_id' => $employee->id,
            'branch_id' => $employee->branch_id,
            'work_date' => now()->toDateString(),
            'status' => Attendance::STATUS_PRESENT,
            'source' => 'manual',
            'is_manual' => true,
        ]);
    }

    public function test_manager_only_sees_their_branch_attendance(): void
    {
        $this->attendance($this->employeeHq);
        $this->attendance($this->employeeOrmoc);

        $this->actingAs($this->managerHq)->get('/attendance')
            ->assertOk()
            ->assertSee('HQ Staff')
            ->assertDontSee('Ormoc Staff');
    }

    public function test_manager_cannot_open_attendance_outside_their_branch(): void
    {
        $other = $this->attendance($this->employeeOrmoc);

        $this->actingAs($this->managerHq)->get('/attendance/'.$other->id.'/edit')->assertForbidden();
        $this->actingAs($this->managerHq)->delete('/attendance/'.$other->id)->assertForbidden();
    }

    public function test_manager_without_a_branch_sees_nothing(): void
    {
        $this->attendance($this->employeeHq);
        $orphan = $this->user(User::ROLE_MANAGER, 'orphan@test.local', null);

        $this->actingAs($orphan)->get('/attendance')->assertOk()->assertDontSee('HQ Staff');
    }

    public function test_hr_is_unrestricted(): void
    {
        $this->attendance($this->employeeHq);
        $this->attendance($this->employeeOrmoc);

        $this->actingAs($this->user(User::ROLE_HR, 'hr@test.local', $this->hq->id))
            ->get('/attendance')
            ->assertOk()
            ->assertSee('HQ Staff')
            ->assertSee('Ormoc Staff');
    }

    public function test_manager_cannot_decide_a_leave_request_outside_their_branch(): void
    {
        $leave = LeaveRequest::create([
            'employee_id' => $this->employeeOrmoc->id,
            'leave_type_id' => LeaveType::create(['name' => 'Vacation', 'code' => 'VL'])->id,
            'date_from' => '2026-06-01',
            'date_to' => '2026-06-01',
            'days' => 1,
            'is_paid' => true,
            'status' => LeaveRequest::STATUS_PENDING,
        ]);

        $this->actingAs($this->managerHq)
            ->post('/leave-requests/'.$leave->id.'/decide', ['status' => 'approved'])
            ->assertForbidden();

        $this->assertSame(LeaveRequest::STATUS_PENDING, $leave->fresh()->status);
    }

    public function test_filings_notify_only_the_filers_branch_managers(): void
    {
        $employeeUser = $this->user(User::ROLE_EMPLOYEE, 'emp-hq@test.local', $this->hq->id);
        $this->employeeHq->update(['user_id' => $employeeUser->id]);

        $this->actingAs($employeeUser)->post('/me/leave', [
            'leave_type_id' => LeaveType::create(['name' => 'Vacation', 'code' => 'VL'])->id,
            'date_from' => '2026-06-01',
            'date_to' => '2026-06-02',
        ])->assertRedirect();

        $this->assertSame(1, $this->managerHq->unreadNotifications()->count());
        $this->assertSame(0, $this->managerOrmoc->unreadNotifications()->count());
    }
}
