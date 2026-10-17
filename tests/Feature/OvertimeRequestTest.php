<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\OvertimeRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OvertimeRequestTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::create(['name' => 'HQ', 'code' => 'HQ']);
    }

    private function employeeUser(): array
    {
        $user = User::create([
            'name' => 'Emp', 'email' => 'emp@test.local',
            'password' => 'password', 'role' => User::ROLE_EMPLOYEE, 'is_active' => true,
        ]);
        $employee = Employee::create([
            'employee_no' => 'E-001', 'first_name' => 'Juan', 'last_name' => 'Cruz', 'user_id' => $user->id,
            'branch_id' => $this->branch->id,
        ]);

        return [$user, $employee];
    }

    public function test_employee_can_file_overtime(): void
    {
        [$user, $employee] = $this->employeeUser();

        $this->actingAs($user)->post('/me/ot', [
            'work_date' => '2026-06-01',
            'hours' => 2,
            'reason' => 'Month-end close',
        ])->assertRedirect();

        $this->assertDatabaseHas('overtime_requests', [
            'employee_id' => $employee->id,
            'status' => OvertimeRequest::STATUS_PENDING,
        ]);
    }

    public function test_manager_can_approve_and_reject(): void
    {
        [$user, $employee] = $this->employeeUser();
        $manager = User::create([
            'name' => 'Mgr', 'email' => 'mgr@test.local',
            'password' => 'password', 'role' => User::ROLE_MANAGER, 'is_active' => true,
            'branch_id' => $this->branch->id,
        ]);

        $approve = OvertimeRequest::create([
            'employee_id' => $employee->id, 'work_date' => '2026-06-01', 'hours' => 2,
        ]);
        $reject = OvertimeRequest::create([
            'employee_id' => $employee->id, 'work_date' => '2026-06-02', 'hours' => 3,
        ]);

        $this->actingAs($manager)
            ->post('/ot/'.$approve->id.'/decide', ['decision' => 'approve', 'decision_remarks' => 'OK'])
            ->assertRedirect();
        $this->actingAs($manager)
            ->post('/ot/'.$reject->id.'/decide', ['decision' => 'reject'])
            ->assertRedirect();

        $this->assertSame(OvertimeRequest::STATUS_APPROVED, $approve->fresh()->status);
        $this->assertSame($manager->id, $approve->fresh()->decided_by);
        $this->assertSame(OvertimeRequest::STATUS_REJECTED, $reject->fresh()->status);
    }
}
