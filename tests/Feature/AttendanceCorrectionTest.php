<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceCorrectionTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: Employee} */
    private function employeeUser(): array
    {
        $user = User::create([
            'name' => 'Emp', 'email' => 'emp@test.local',
            'password' => 'password', 'role' => User::ROLE_EMPLOYEE, 'is_active' => true,
        ]);

        $employee = Employee::create([
            'employee_no' => 'EMP0001', 'user_id' => $user->id,
            'first_name' => 'Test', 'last_name' => 'Employee',
            'basic_salary' => 20000, 'tax_status' => 'S',
            'employment_status' => 'regular', 'is_active' => true,
        ]);

        return [$user, $employee];
    }

    public function test_employee_can_submit_a_correction_request(): void
    {
        [$user, $employee] = $this->employeeUser();

        $this->actingAs($user)->get('/me/tk')->assertOk()->assertSee('Request a punch correction');

        $this->actingAs($user)->post('/me/tk', [
            'work_date' => '2026-10-01',
            'time_in' => '08:00',
            'time_out' => '17:00',
            'reason' => 'Forgot to punch out',
        ])->assertRedirect()->assertSessionHas('ok');

        $correction = AttendanceCorrection::sole();
        $this->assertSame($employee->id, $correction->employee_id);
        $this->assertSame(AttendanceCorrection::STATUS_PENDING, $correction->status);
    }

    public function test_correction_needs_at_least_one_punch(): void
    {
        [$user] = $this->employeeUser();

        $this->actingAs($user)->post('/me/tk', [
            'work_date' => '2026-10-01',
            'reason' => 'Nothing entered',
        ])->assertSessionHasErrors('time_in');

        $this->assertSame(0, AttendanceCorrection::count());
    }

    public function test_hr_approval_writes_the_corrected_attendance(): void
    {
        [$user, $employee] = $this->employeeUser();

        $hr = User::create([
            'name' => 'HR', 'email' => 'hr@test.local',
            'password' => 'password', 'role' => User::ROLE_HR, 'is_active' => true,
        ]);

        $correction = AttendanceCorrection::create([
            'employee_id' => $employee->id,
            'work_date' => '2026-10-01',
            'time_in' => '2026-10-01 08:00:00',
            'time_out' => '2026-10-01 17:00:00',
            'reason' => 'Missed punch',
            'status' => AttendanceCorrection::STATUS_PENDING,
        ]);

        $this->actingAs($hr)->get('/attendance/corrections')->assertOk()->assertSee($employee->full_name);

        $this->actingAs($hr)->post('/attendance/corrections/'.$correction->id.'/decide', [
            'decision' => 'approve',
        ])->assertRedirect()->assertSessionHas('ok');

        $this->assertSame(AttendanceCorrection::STATUS_APPROVED, $correction->fresh()->status);

        $attendance = Attendance::sole();
        $this->assertSame($employee->id, $attendance->employee_id);
        $this->assertSame('correction', $attendance->source);
        $this->assertNotNull($attendance->time_in);
        $this->assertNotNull($attendance->time_out);
    }

    public function test_employee_cannot_open_hr_corrections(): void
    {
        [$user] = $this->employeeUser();

        $this->actingAs($user)->get('/attendance/corrections')->assertForbidden();
    }
}
