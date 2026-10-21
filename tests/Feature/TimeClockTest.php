<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\TimeClockIp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimeClockTest extends TestCase
{
    use RefreshDatabase;

    private function employee(): Employee
    {
        return Employee::create([
            'employee_no' => 'EMP0001',
            'pin' => '1234',
            'first_name' => 'Test',
            'last_name' => 'Employee',
            'basic_salary' => 20000,
            'tax_status' => 'S',
            'employment_status' => 'regular',
            'is_active' => true,
        ]);
    }

    public function test_time_clock_sits_on_login_page(): void
    {
        $this->get('/login')->assertOk()->assertSee('Time Clock')->assertSee('Staff sign in');
    }

    public function test_punch_in_records_time_in(): void
    {
        $employee = $this->employee();

        $this->post('/time-clock/punch', [
            'employee_no' => 'EMP0001',
            'pin' => '1234',
            'punch' => 'in',
        ])->assertRedirect()->assertSessionHas('punch_ok');

        $attendance = Attendance::where('employee_id', $employee->id)->sole();
        $this->assertNotNull($attendance->time_in);
        $this->assertSame('web', $attendance->source);
    }

    public function test_wrong_pin_is_rejected(): void
    {
        $this->employee();

        $this->post('/time-clock/punch', [
            'employee_no' => 'EMP0001',
            'pin' => '9999',
            'punch' => 'in',
        ])->assertSessionHas('punch_error');

        $this->assertSame(0, Attendance::count());
    }

    public function test_full_bundy_records_all_punches(): void
    {
        $this->employee();

        foreach (['in', 'break1_out', 'break1_in', 'lunch_out', 'lunch_in', 'break2_out', 'break2_in', 'out'] as $punch) {
            $this->post('/time-clock/punch', [
                'employee_no' => 'EMP0001',
                'pin' => '1234',
                'punch' => $punch,
            ]);
        }

        $attendance = Attendance::sole();
        $this->assertNotNull($attendance->time_in);
        $this->assertNotNull($attendance->break1_out);
        $this->assertNotNull($attendance->lunch_in);
        $this->assertNotNull($attendance->break2_in);
        $this->assertNotNull($attendance->time_out);
    }

    public function test_punch_blocked_from_unlisted_ip(): void
    {
        $this->employee();
        TimeClockIp::create(['cidr' => '10.0.0.0/8']);

        $this->post('/time-clock/punch', [
            'employee_no' => 'EMP0001',
            'pin' => '1234',
            'punch' => 'in',
        ])->assertSessionHas('punch_error');

        $this->assertSame(0, Attendance::count());
    }

    public function test_punch_allowed_from_matching_ip(): void
    {
        $this->employee();
        TimeClockIp::create(['cidr' => '127.0.0.1']);

        $this->post('/time-clock/punch', [
            'employee_no' => 'EMP0001',
            'pin' => '1234',
            'punch' => 'in',
        ])->assertSessionHas('punch_ok');

        $this->assertSame(1, Attendance::count());
    }
}
