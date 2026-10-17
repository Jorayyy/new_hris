<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceExceptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_attendance_exceptions(): void
    {
        $manager = User::create([
            'name' => 'Mgr', 'email' => 'mgr@test.local',
            'password' => 'password', 'role' => User::ROLE_MANAGER, 'is_active' => true,
        ]);
        $employee = Employee::create(['employee_no' => 'E-001', 'first_name' => 'Juan', 'last_name' => 'Cruz']);

        Attendance::create(['employee_id' => $employee->id, 'work_date' => '2026-06-01', 'status' => Attendance::STATUS_ABSENT]);
        Attendance::create([
            'employee_id' => $employee->id, 'work_date' => '2026-06-02',
            'time_in' => '2026-06-02 09:30:00', 'time_out' => '2026-06-02 18:00:00',
            'late_minutes' => 30, 'status' => Attendance::STATUS_PRESENT,
        ]);
        Attendance::create([
            'employee_id' => $employee->id, 'work_date' => '2026-06-03',
            'time_in' => '2026-06-03 09:00:00', 'status' => Attendance::STATUS_PRESENT,
        ]);

        $this->actingAs($manager)
            ->get('/attendance/exceptions?from=2026-06-01&to=2026-06-30')
            ->assertOk()
            ->assertSee('Juan Cruz')
            ->assertSee('Absent')
            ->assertSee('Missing out');
    }
}
