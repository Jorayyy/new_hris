<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeShift;
use App\Models\Shift;
use App\Models\ShiftSwapRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShiftSwapTest extends TestCase
{
    use RefreshDatabase;

    private function employee(string $no, ?User $user = null): Employee
    {
        return Employee::create([
            'employee_no' => $no, 'first_name' => 'Juan', 'last_name' => $no, 'user_id' => $user?->id,
        ]);
    }

    private function assign(Employee $employee, Shift $shift): void
    {
        EmployeeShift::create([
            'employee_id' => $employee->id, 'shift_id' => $shift->id,
            'effective_from' => '2026-01-01', 'published_at' => now(),
        ]);
    }

    private function shift(string $name): Shift
    {
        return Shift::create(['name' => $name, 'time_in' => '09:00', 'time_out' => '18:00']);
    }

    public function test_employee_can_request_a_swap(): void
    {
        $user = User::create([
            'name' => 'Emp', 'email' => 'emp@test.local',
            'password' => 'password', 'role' => User::ROLE_EMPLOYEE, 'is_active' => true,
        ]);
        $me = $this->employee('E-001', $user);
        $colleague = $this->employee('E-002');
        $day = $this->shift('Day');
        $night = $this->shift('Night');
        $this->assign($me, $day);
        $this->assign($colleague, $night);

        $this->actingAs($user)->post('/me/swaps', [
            'target_id' => $colleague->id,
            'swap_date' => '2026-06-01',
        ])->assertRedirect();

        $this->assertDatabaseHas('shift_swap_requests', [
            'requester_id' => $me->id,
            'target_id' => $colleague->id,
            'requester_shift_id' => $day->id,
            'target_shift_id' => $night->id,
            'status' => ShiftSwapRequest::STATUS_PENDING,
        ]);
    }

    public function test_manager_approval_swaps_the_shifts(): void
    {
        $user = User::create([
            'name' => 'Emp', 'email' => 'emp@test.local',
            'password' => 'password', 'role' => User::ROLE_EMPLOYEE, 'is_active' => true,
        ]);
        $manager = User::create([
            'name' => 'Mgr', 'email' => 'mgr@test.local',
            'password' => 'password', 'role' => User::ROLE_MANAGER, 'is_active' => true,
        ]);
        $me = $this->employee('E-001', $user);
        $colleague = $this->employee('E-002');
        $day = $this->shift('Day');
        $night = $this->shift('Night');
        $this->assign($me, $day);
        $this->assign($colleague, $night);

        $swap = ShiftSwapRequest::create([
            'requester_id' => $me->id, 'target_id' => $colleague->id,
            'swap_date' => '2026-06-01', 'requester_shift_id' => $day->id, 'target_shift_id' => $night->id,
        ]);

        $this->actingAs($manager)
            ->post('/swaps/'.$swap->id.'/decide', ['decision' => 'approve'])
            ->assertRedirect();

        $this->assertSame($night->id, $me->shiftForDate('2026-06-01')->id);
        $this->assertSame($day->id, $colleague->shiftForDate('2026-06-01')->id);
        $this->assertSame(ShiftSwapRequest::STATUS_APPROVED, $swap->fresh()->status);
    }

    public function test_manager_rejection_leaves_shifts_unchanged(): void
    {
        $manager = User::create([
            'name' => 'Mgr', 'email' => 'mgr@test.local',
            'password' => 'password', 'role' => User::ROLE_MANAGER, 'is_active' => true,
        ]);
        $me = $this->employee('E-001');
        $colleague = $this->employee('E-002');
        $day = $this->shift('Day');
        $night = $this->shift('Night');
        $this->assign($me, $day);
        $this->assign($colleague, $night);

        $swap = ShiftSwapRequest::create([
            'requester_id' => $me->id, 'target_id' => $colleague->id,
            'swap_date' => '2026-06-01', 'requester_shift_id' => $day->id, 'target_shift_id' => $night->id,
        ]);

        $this->actingAs($manager)
            ->post('/swaps/'.$swap->id.'/decide', ['decision' => 'reject'])
            ->assertRedirect();

        $this->assertSame($day->id, $me->shiftForDate('2026-06-01')->id);
        $this->assertSame(ShiftSwapRequest::STATUS_REJECTED, $swap->fresh()->status);
    }
}
