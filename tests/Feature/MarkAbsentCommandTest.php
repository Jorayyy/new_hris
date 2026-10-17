<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeShift;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Shift;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarkAbsentCommandTest extends TestCase
{
    use RefreshDatabase;

    private const DAY = '2026-06-01';

    private function scheduledEmployee(array $attributes = []): Employee
    {
        $employee = Employee::create(array_merge([
            'employee_no' => 'E-'.uniqid(),
            'first_name' => 'Juan',
            'last_name' => 'Cruz',
        ], $attributes));

        EmployeeShift::create([
            'employee_id' => $employee->id,
            'shift_id' => Shift::create(['name' => 'Day', 'time_in' => '09:00', 'time_out' => '18:00'])->id,
            'effective_from' => '2026-01-01',
            'published_at' => now(),
        ]);

        return $employee;
    }

    public function test_it_marks_a_scheduled_employee_absent(): void
    {
        $employee = $this->scheduledEmployee();

        $this->artisan('attendance:mark-absent', ['date' => self::DAY])->assertSuccessful();

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $employee->id,
            'status' => Attendance::STATUS_ABSENT,
            'source' => 'auto',
        ]);
    }

    public function test_it_skips_rest_days_leave_and_existing_records(): void
    {
        $resting = $this->scheduledEmployee(['rest_days' => [1]]);

        $onLeave = $this->scheduledEmployee();
        LeaveRequest::create([
            'employee_id' => $onLeave->id,
            'leave_type_id' => LeaveType::create(['name' => 'VL', 'code' => 'VL'])->id,
            'date_from' => self::DAY,
            'date_to' => self::DAY,
            'days' => 1,
            'status' => LeaveRequest::STATUS_APPROVED,
        ]);

        $present = $this->scheduledEmployee();
        Attendance::create([
            'employee_id' => $present->id,
            'work_date' => self::DAY,
            'time_in' => self::DAY.' 09:00:00',
            'status' => Attendance::STATUS_PRESENT,
        ]);

        $this->artisan('attendance:mark-absent', ['date' => self::DAY])->assertSuccessful();

        $this->assertDatabaseMissing('attendances', ['employee_id' => $resting->id]);
        $this->assertDatabaseMissing('attendances', ['employee_id' => $onLeave->id]);
        $this->assertDatabaseHas('attendances', ['employee_id' => $present->id, 'status' => Attendance::STATUS_PRESENT]);
        $this->assertSame(1, Attendance::count());
    }
}
