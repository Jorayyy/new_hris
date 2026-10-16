<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\Payslip;
use Carbon\Carbon;

/**
 * Final pay for a separated employee: unpaid days in the last period, pro-rated
 * 13th month, cash conversion of convertible leave credits, less outstanding
 * loans and recurring deductions.
 */
class FinalPayService
{
    public function compute(Employee $employee, string $asOf): array
    {
        $asOfDate = Carbon::parse($asOf)->startOfDay();
        $daily = $employee->dailyRate();

        $lastPaidPeriodEnd = Payslip::query()
            ->where('employee_id', $employee->id)
            ->with('run.period')
            ->get()
            ->map(fn (Payslip $p) => $p->run?->period?->date_to)
            ->filter(fn ($date) => $date && $date->lte($asOfDate))
            ->max();

        $start = $lastPaidPeriodEnd
            ? $lastPaidPeriodEnd->copy()->addDay()
            : Carbon::parse($employee->date_hired ?? $asOfDate->copy()->startOfYear());

        $presentDays = $start->lte($asOfDate)
            ? Attendance::query()
                ->where('employee_id', $employee->id)
                ->where('status', Attendance::STATUS_PRESENT)
                ->whereBetween('work_date', [$start->toDateString(), $asOfDate->toDateString()])
                ->count()
            : 0;

        $basicPay = round($presentDays * $daily, 2);

        $ytdBasic = (float) Payslip::query()
            ->where('employee_id', $employee->id)
            ->whereHas('run.period', fn ($q) => $q->whereYear('date_to', $asOfDate->year))
            ->sum('basic_pay');

        $thirteenthMonth = round(($ytdBasic + $basicPay) / 12, 2);

        $leaveDays = LeaveBalance::query()
            ->where('employee_id', $employee->id)
            ->where('year', $asOfDate->year)
            ->whereHas('leaveType', fn ($q) => $q->where('is_cash_convertible', true))
            ->get()
            ->sum(fn (LeaveBalance $b) => $b->remaining);

        $leaveConversion = round($leaveDays * $daily, 2);
        $loanBalance = round((float) $employee->loans()->active()->sum('balance'), 2);
        $otherDeductions = round($employee->deductions->sum(fn ($d) => (float) $d->amount), 2);

        $net = round($basicPay + $thirteenthMonth + $leaveConversion - $loanBalance - $otherDeductions, 2);

        return [
            'as_of' => $asOfDate->toDateString(),
            'period_start' => $start->toDateString(),
            'daily_rate' => $daily,
            'present_days' => $presentDays,
            'basic_pay' => $basicPay,
            'ytd_basic' => round($ytdBasic, 2),
            'thirteenth_month' => $thirteenthMonth,
            'leave_days' => round((float) $leaveDays, 2),
            'leave_conversion' => $leaveConversion,
            'loan_balance' => $loanBalance,
            'other_deductions' => $otherDeductions,
            'net_pay' => $net,
        ];
    }
}
