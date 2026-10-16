<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Holiday;
use Carbon\Carbon;

/**
 * DOLE premium pay rules. Classifies each worked day (ordinary, rest day,
 * special holiday, regular holiday and their rest-day combinations) and returns
 * a per-period pay breakdown: basic, rest-day premium, holiday premium,
 * overtime and night differential.
 *
 * Rates for a worked day (regular hours): ordinary 100%, rest day 130%,
 * special day 130%, special on rest day 150%, regular holiday 200%,
 * regular holiday on rest day 260%. Overtime adds a 125% factor on an ordinary
 * day and 130% on any premium day. Night differential is 10% of the day rate.
 */
class PremiumPayService
{
    public const NIGHT_DIFF_RATE = 0.10;

    public const REST_DAY_PREMIUM = 0.30;

    public const WORKED_MULTIPLIER = [
        'ordinary' => 1.00,
        'rest' => 1.30,
        'special' => 1.30,
        'special_rest' => 1.50,
        'regular_holiday' => 2.00,
        'regular_holiday_rest' => 2.60,
    ];

    public const OT_FACTOR = [
        'ordinary' => 1.25,
        'rest' => 1.30,
        'special' => 1.30,
        'special_rest' => 1.30,
        'regular_holiday' => 1.30,
        'regular_holiday_rest' => 1.30,
    ];

    public function classify(?string $holidayType, bool $restDay): string
    {
        if ($holidayType !== null) {
            $base = $holidayType === Holiday::TYPE_REGULAR ? 'regular_holiday' : 'special';

            return $restDay ? $base.'_rest' : $base;
        }

        return $restDay ? 'rest' : 'ordinary';
    }

    /**
     * @param  iterable<Attendance>  $attendances  attendance rows for the period
     * @param  array<string, string>  $holidays  date (Y-m-d) => holiday type
     * @param  array<int, int>  $restDays  weekday numbers (0=Sun .. 6=Sat)
     * @return array{basic: float, rest_day: float, holiday: float, overtime: float, night_diff: float}
     */
    public function summarize(iterable $attendances, array $holidays, array $restDays, float $daily, float $hourly): array
    {
        $totals = ['basic' => 0.0, 'rest_day' => 0.0, 'holiday' => 0.0, 'overtime' => 0.0, 'night_diff' => 0.0];
        $workedDates = [];

        foreach ($attendances as $row) {
            if ($row->status !== Attendance::STATUS_PRESENT) {
                continue;
            }

            $date = optional($row->work_date)->toDateString();
            if (! $date) {
                continue;
            }

            $workedDates[$date] = true;
            $restDay = in_array((int) Carbon::parse($date)->dayOfWeek, $restDays, true);
            $class = $this->classify($holidays[$date] ?? null, $restDay);
            $multiplier = self::WORKED_MULTIPLIER[$class];

            $totals['basic'] += $daily;
            $totals['rest_day'] += $restDay ? self::REST_DAY_PREMIUM * $daily : 0.0;
            $totals['holiday'] += max(0.0, $multiplier - 1.00 - ($restDay ? self::REST_DAY_PREMIUM : 0.0)) * $daily;
            $totals['overtime'] += (float) $row->overtime_hours * $hourly * $multiplier * self::OT_FACTOR[$class];
            $totals['night_diff'] += (float) $row->night_diff_hours * $hourly * $multiplier * self::NIGHT_DIFF_RATE;
        }

        // Unworked regular holidays are paid at 100% even without attendance.
        foreach ($holidays as $date => $type) {
            if ($type === Holiday::TYPE_REGULAR && ! isset($workedDates[$date])) {
                $totals['holiday'] += $daily;
            }
        }

        return array_map(fn ($amount) => round($amount, 2), $totals);
    }
}
