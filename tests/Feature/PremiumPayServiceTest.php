<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Holiday;
use App\Services\PremiumPayService;
use Tests\TestCase;

class PremiumPayServiceTest extends TestCase
{
    private const DAILY = 1000.0;

    private const HOURLY = 125.0;

    /** 2026-01-05 is a Monday (weekday 1); 2026-01-11 is a Sunday (weekday 0). */
    private function row(?string $date, array $overrides = []): Attendance
    {
        return new Attendance(array_merge([
            'work_date' => $date,
            'status' => Attendance::STATUS_PRESENT,
            'overtime_hours' => 0,
            'night_diff_hours' => 0,
        ], $overrides));
    }

    private function summarize(array $rows, array $holidays = [], array $restDays = []): array
    {
        return (new PremiumPayService)->summarize($rows, $holidays, $restDays, self::DAILY, self::HOURLY);
    }

    public function test_ordinary_worked_day(): void
    {
        $totals = $this->summarize([$this->row('2026-01-05')]);

        $this->assertSame(1000.0, $totals['basic']);
        $this->assertSame(0.0, $totals['rest_day']);
        $this->assertSame(0.0, $totals['holiday']);
    }

    public function test_worked_rest_day_adds_thirty_percent(): void
    {
        $totals = $this->summarize([$this->row('2026-01-05')], [], [1]);

        $this->assertSame(1000.0, $totals['basic']);
        $this->assertSame(300.0, $totals['rest_day']);
    }

    public function test_worked_special_holiday(): void
    {
        $totals = $this->summarize([$this->row('2026-01-05')], ['2026-01-05' => Holiday::TYPE_SPECIAL]);

        $this->assertSame(1000.0, $totals['basic']);
        $this->assertSame(300.0, $totals['holiday']);
    }

    public function test_worked_regular_holiday(): void
    {
        $totals = $this->summarize([$this->row('2026-01-05')], ['2026-01-05' => Holiday::TYPE_REGULAR]);

        $this->assertSame(1000.0, $totals['basic']);
        $this->assertSame(1000.0, $totals['holiday']);
    }

    public function test_unworked_regular_holiday_is_paid(): void
    {
        $totals = $this->summarize([], ['2026-01-05' => Holiday::TYPE_REGULAR]);

        $this->assertSame(0.0, $totals['basic']);
        $this->assertSame(1000.0, $totals['holiday']);
    }

    public function test_unworked_special_holiday_is_not_paid(): void
    {
        $totals = $this->summarize([], ['2026-01-05' => Holiday::TYPE_SPECIAL]);

        $this->assertSame(0.0, $totals['holiday']);
    }

    public function test_regular_holiday_on_rest_day_stacks_rest_and_holiday(): void
    {
        $totals = $this->summarize([$this->row('2026-01-05')], ['2026-01-05' => Holiday::TYPE_REGULAR], [1]);

        $this->assertSame(1000.0, $totals['basic']);
        $this->assertSame(300.0, $totals['rest_day']);
        $this->assertSame(1300.0, $totals['holiday']);
    }

    public function test_overtime_on_rest_day_uses_169_percent(): void
    {
        $totals = $this->summarize([$this->row('2026-01-05', ['overtime_hours' => 2])], [], [1]);

        $this->assertSame(422.5, $totals['overtime']);
    }

    public function test_night_differential_is_ten_percent_of_day_rate(): void
    {
        $totals = $this->summarize([$this->row('2026-01-05', ['night_diff_hours' => 3])]);

        $this->assertSame(37.5, $totals['night_diff']);
    }

    public function test_absent_rows_are_ignored(): void
    {
        $totals = $this->summarize([$this->row('2026-01-05', ['status' => Attendance::STATUS_ABSENT])]);

        $this->assertSame(0.0, $totals['basic']);
    }
}
