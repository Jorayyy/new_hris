<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeShift;
use App\Models\ScheduleChangeRequest;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleChangeRequestTest extends TestCase
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

    private function shift(string $name): Shift
    {
        return Shift::create(['name' => $name, 'time_in' => '09:00', 'time_out' => '18:00']);
    }

    public function test_employee_can_file_a_schedule_change(): void
    {
        [$user, $employee] = $this->employeeUser();
        $day = $this->shift('Day');
        $night = $this->shift('Night');
        EmployeeShift::create([
            'employee_id' => $employee->id, 'shift_id' => $day->id,
            'effective_from' => '2026-01-01', 'published_at' => now(),
        ]);

        $this->actingAs($user)->post('/me/schedule', [
            'work_date' => '2026-06-01',
            'shift_id' => $night->id,
            'reason' => 'Coverage',
        ])->assertRedirect();

        $this->assertDatabaseHas('schedule_change_requests', [
            'employee_id' => $employee->id,
            'current_shift_id' => $day->id,
            'shift_id' => $night->id,
            'status' => ScheduleChangeRequest::STATUS_PENDING,
        ]);
    }

    public function test_approval_applies_a_one_day_override(): void
    {
        [$user, $employee] = $this->employeeUser();
        $manager = User::create([
            'name' => 'Mgr', 'email' => 'mgr@test.local',
            'password' => 'password', 'role' => User::ROLE_MANAGER, 'is_active' => true,
            'branch_id' => $this->branch->id,
        ]);
        $day = $this->shift('Day');
        $night = $this->shift('Night');
        EmployeeShift::create([
            'employee_id' => $employee->id, 'shift_id' => $day->id,
            'effective_from' => '2026-01-01', 'published_at' => now(),
        ]);

        $change = ScheduleChangeRequest::create([
            'employee_id' => $employee->id, 'work_date' => '2026-06-01',
            'current_shift_id' => $day->id, 'shift_id' => $night->id,
        ]);

        $this->actingAs($manager)
            ->post('/schedule-changes/'.$change->id.'/decide', ['decision' => 'approve'])
            ->assertRedirect();

        $this->assertSame($night->id, $employee->shiftForDate('2026-06-01')->id);
        $this->assertSame($day->id, $employee->shiftForDate('2026-06-10')->id);
        $this->assertSame(ScheduleChangeRequest::STATUS_APPROVED, $change->fresh()->status);
    }

    public function test_rejection_applies_no_override(): void
    {
        [$user, $employee] = $this->employeeUser();
        $manager = User::create([
            'name' => 'Mgr', 'email' => 'mgr@test.local',
            'password' => 'password', 'role' => User::ROLE_MANAGER, 'is_active' => true,
            'branch_id' => $this->branch->id,
        ]);
        $day = $this->shift('Day');
        $night = $this->shift('Night');
        EmployeeShift::create([
            'employee_id' => $employee->id, 'shift_id' => $day->id,
            'effective_from' => '2026-01-01', 'published_at' => now(),
        ]);

        $change = ScheduleChangeRequest::create([
            'employee_id' => $employee->id, 'work_date' => '2026-06-01',
            'current_shift_id' => $day->id, 'shift_id' => $night->id,
        ]);

        $this->actingAs($manager)
            ->post('/schedule-changes/'.$change->id.'/decide', ['decision' => 'reject'])
            ->assertRedirect();

        $this->assertSame($day->id, $employee->shiftForDate('2026-06-01')->id);
        $this->assertSame(ScheduleChangeRequest::STATUS_REJECTED, $change->fresh()->status);
    }
}
