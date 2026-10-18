<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Shift;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Turns raw time-in/time-out into DTR figures: late, undertime, overtime, night differential.
 * Pure time math lives in compute() so it is unit-testable.
 */
class DtrService
{
    public const NIGHT_START_HOUR = 22;

    public const NIGHT_END_HOUR = 6;

    public function compute(CarbonInterface $timeIn, CarbonInterface $timeOut, ?Shift $shift): array
    {
        $in = Carbon::instance($timeIn);
        $out = Carbon::instance($timeOut);

        if ($out->lessThanOrEqualTo($in)) {
            $out = $out->copy()->addDay();
        }

        $requiredHours = $shift ? (float) $shift->required_hours : 8.0;
        $breakHours = $shift ? $shift->break_minutes / 60 : 1.0;
        $grace = $shift ? (int) $shift->grace_minutes : 0;

        $rawMinutes = $in->diffInMinutes($out, false);

        $lateMinutes = 0;
        $undertimeMinutes = 0;
        $overtimeHours = 0.0;

        if ($shift) {
            [$expectedStart, $expectedEnd] = $this->expectedWindow($in, $shift);
            $lateMinutes = max(0, $expectedStart->diffInMinutes($in, false) - $grace);

            $endDiff = $expectedEnd->diffInMinutes($out, false);
            if ($endDiff >= 0) {
                $overtimeHours = round($endDiff / 60, 2);
            } else {
                $undertimeMinutes = -$endDiff;
            }
        }

        $hoursWorked = max(0, $rawMinutes / 60 - ($rawMinutes / 60 > $breakHours ? $breakHours : 0));

        return [
            'hours_worked' => round($hoursWorked, 2),
            'late_minutes' => (int) $lateMinutes,
            'undertime_minutes' => (int) $undertimeMinutes,
            'overtime_hours' => $overtimeHours,
            'night_diff_hours' => $this->nightDiffHours($in, $out),
            'required_hours' => $requiredHours,
        ];
    }

    /** @return array{0: Carbon, 1: Carbon} */
    public function expectedWindow(CarbonInterface $in, Shift $shift): array
    {
        $day = Carbon::instance($in)->startOfDay();
        $start = Carbon::parse($day->toDateString().' '.$shift->time_in);
        $end = Carbon::parse($day->toDateString().' '.$shift->time_out);
        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay();
        }

        return [$start, $end];
    }

    public function nightDiffHours(CarbonInterface $timeIn, CarbonInterface $timeOut): float
    {
        $in = Carbon::instance($timeIn);
        $out = Carbon::instance($timeOut);
        if ($out->lessThanOrEqualTo($in)) {
            $out = $out->copy()->addDay();
        }

        $total = 0;
        $cursor = $in->copy()->startOfDay();
        while ($cursor->lessThan($out)) {
            $total += $this->overlapMinutes($in, $out, $cursor->copy()->setTime(self::NIGHT_START_HOUR, 0), $cursor->copy()->addDay()->setTime(self::NIGHT_END_HOUR, 0));
            $cursor->addDay();
        }

        return round($total / 60, 2);
    }

    private function overlapMinutes(CarbonInterface $aStart, CarbonInterface $aEnd, CarbonInterface $bStart, CarbonInterface $bEnd): int
    {
        $start = $aStart->greaterThan($bStart) ? $aStart : $bStart;
        $end = $aEnd->lessThan($bEnd) ? $aEnd : $bEnd;

        return $end->greaterThan($start) ? (int) $start->diffInMinutes($end) : 0;
    }

    public function process(Attendance $attendance): Attendance
    {
        if (! $attendance->time_in || ! $attendance->time_out) {
            $attendance->status = $attendance->time_in ? Attendance::STATUS_PRESENT : Attendance::STATUS_ABSENT;
            $attendance->save();

            return $attendance;
        }

        $shift = $attendance->shift ?? $attendance->employee?->shiftForDate($attendance->work_date);
        $result = $this->compute($attendance->time_in, $attendance->time_out, $shift);

        $attendance->fill([
            'shift_id' => $shift?->id,
            'hours_worked' => $result['hours_worked'],
            'late_minutes' => $result['late_minutes'],
            'undertime_minutes' => $result['undertime_minutes'],
            'overtime_hours' => $result['overtime_hours'],
            'night_diff_hours' => $result['night_diff_hours'],
            'status' => Attendance::STATUS_PRESENT,
        ])->save();

        return $attendance;
    }

    /**
     * Aggregate an employee's DTR rows inside a date range.
     */
    public function summarize(Employee $employee, string $from, string $to): array
    {
        $rows = Attendance::query()
            ->where('employee_id', $employee->id)
            ->workDateBetween($from, $to)
            ->get();

        return [
            'days_worked' => $rows->where('status', Attendance::STATUS_PRESENT)->count(),
            'hours_worked' => (float) $rows->sum('hours_worked'),
            'late_minutes' => (int) $rows->sum('late_minutes'),
            'undertime_minutes' => (int) $rows->sum('undertime_minutes'),
            'overtime_hours' => (float) $rows->sum('overtime_hours'),
            'night_diff_hours' => (float) $rows->sum('night_diff_hours'),
            'leave_days' => $rows->where('status', Attendance::STATUS_LEAVE)->count(),
        ];
    }
}
