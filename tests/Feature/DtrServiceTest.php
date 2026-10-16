<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Shift;
use App\Services\DtrService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DtrServiceTest extends TestCase
{
    use RefreshDatabase;

    private DtrService $dtr;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dtr = new DtrService;
    }

    private function dayShift(): Shift
    {
        return new Shift([
            'time_in' => '08:00', 'time_out' => '17:00',
            'break_minutes' => 60, 'grace_minutes' => 10, 'required_hours' => 8,
        ]);
    }

    public function test_late_uses_grace_period(): void
    {
        $result = $this->dtr->compute(
            Carbon::parse('2026-01-05 08:20'),
            Carbon::parse('2026-01-05 17:00'),
            $this->dayShift()
        );

        $this->assertSame(10, $result['late_minutes']);
        $this->assertSame(0, $result['undertime_minutes']);
        $this->assertSame(7.67, $result['hours_worked']);
    }

    public function test_on_time_has_no_late(): void
    {
        $result = $this->dtr->compute(
            Carbon::parse('2026-01-05 08:05'),
            Carbon::parse('2026-01-05 17:00'),
            $this->dayShift()
        );

        $this->assertSame(0, $result['late_minutes']);
    }

    public function test_undertime_detected(): void
    {
        $result = $this->dtr->compute(
            Carbon::parse('2026-01-05 08:00'),
            Carbon::parse('2026-01-05 16:00'),
            $this->dayShift()
        );

        $this->assertSame(60, $result['undertime_minutes']);
        $this->assertSame(0.0, $result['overtime_hours']);
        $this->assertSame(7.0, $result['hours_worked']);
    }

    public function test_overtime_detected(): void
    {
        $result = $this->dtr->compute(
            Carbon::parse('2026-01-05 08:00'),
            Carbon::parse('2026-01-05 18:00'),
            $this->dayShift()
        );

        $this->assertSame(1.0, $result['overtime_hours']);
        $this->assertSame(0, $result['undertime_minutes']);
        $this->assertSame(9.0, $result['hours_worked']);
    }

    public function test_night_differential_on_night_shift(): void
    {
        $shift = new Shift([
            'time_in' => '22:00', 'time_out' => '07:00',
            'break_minutes' => 60, 'grace_minutes' => 10, 'required_hours' => 8,
        ]);

        $result = $this->dtr->compute(
            Carbon::parse('2026-01-05 22:00'),
            Carbon::parse('2026-01-06 07:00'),
            $shift
        );

        $this->assertSame(8.0, $result['night_diff_hours']);
        $this->assertSame(0, $result['undertime_minutes']);
        $this->assertSame(8.0, $result['hours_worked']);
    }

    public function test_cross_midnight_shift_undertime(): void
    {
        $shift = new Shift([
            'time_in' => '22:00', 'time_out' => '07:00',
            'break_minutes' => 60, 'grace_minutes' => 0, 'required_hours' => 8,
        ]);

        $result = $this->dtr->compute(
            Carbon::parse('2026-01-05 22:00'),
            Carbon::parse('2026-01-06 06:00'),
            $shift
        );

        $this->assertSame(60, $result['undertime_minutes']);
    }

    public function test_absent_when_no_time_in(): void
    {
        $employee = Employee::create([
            'employee_no' => 'EMP0001', 'first_name' => 'A', 'last_name' => 'B',
            'basic_salary' => 10000, 'employment_status' => 'regular', 'is_active' => true,
        ]);

        $attendance = new Attendance(['employee_id' => $employee->id, 'work_date' => '2026-01-05', 'status' => Attendance::STATUS_PRESENT]);
        $this->dtr->process($attendance);

        $this->assertSame(Attendance::STATUS_ABSENT, $attendance->status);
    }
}
