<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeShift;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RosterTest extends TestCase
{
    use RefreshDatabase;

    private function hr(): User
    {
        return User::create([
            'name' => 'HR', 'email' => 'hr@test.local',
            'password' => 'password', 'role' => User::ROLE_HR, 'is_active' => true,
        ]);
    }

    private function employee(): Employee
    {
        return Employee::create(['employee_no' => 'E-001', 'first_name' => 'Juan', 'last_name' => 'Cruz']);
    }

    private function shift(string $name): Shift
    {
        return Shift::create(['name' => $name, 'time_in' => '09:00', 'time_out' => '18:00']);
    }

    public function test_assigning_a_shift_closes_the_previous_open_assignment(): void
    {
        $employee = $this->employee();
        $day = $this->shift('Day');
        $night = $this->shift('Night');
        $hr = $this->hr();

        $this->actingAs($hr)->post('/roster', [
            'employee_id' => $employee->id,
            'shift_id' => $day->id,
            'effective_from' => '2026-01-01',
        ])->assertRedirect();

        $this->actingAs($hr)->post('/roster', [
            'employee_id' => $employee->id,
            'shift_id' => $night->id,
            'effective_from' => '2026-02-01',
        ])->assertRedirect();

        $first = EmployeeShift::where('shift_id', $day->id)->first();
        $this->assertSame('2026-01-31', $first->effective_to->toDateString());
        $this->assertSame($day->id, $employee->shiftForDate('2026-01-15')->id);
        $this->assertSame($night->id, $employee->shiftForDate('2026-02-10')->id);
    }

    public function test_shift_assignment_can_be_removed(): void
    {
        $employee = $this->employee();
        $assignment = EmployeeShift::create([
            'employee_id' => $employee->id,
            'shift_id' => $this->shift('Day')->id,
            'effective_from' => '2026-01-01',
        ]);

        $this->actingAs($this->hr())
            ->delete('/roster/'.$assignment->id)
            ->assertRedirect();

        $this->assertDatabaseMissing('employee_shifts', ['id' => $assignment->id]);
    }
}
