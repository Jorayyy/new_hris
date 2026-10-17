<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Payslip;

/**
 * 13th month pay: total basic salary earned in the calendar year divided by 12
 * (Philippine Labor Code). The pay itself is released at year end; this only
 * projects it from payslips already on file.
 */
class ThirteenthMonthService
{
    public const TAX_EXEMPT_CAP = 90000.0;

    public function summary(Employee $employee, ?int $year = null): array
    {
        $year ??= (int) now()->year;

        $payslips = Payslip::query()
            ->where('employee_id', $employee->id)
            ->with('run.period')
            ->whereHas('run.period', fn ($query) => $query->whereYear('date_to', $year))
            ->get();

        $ytdBasic = round((float) $payslips->sum('basic_pay'), 2);
        $months = $payslips
            ->map(fn (Payslip $payslip) => $payslip->run?->period?->date_to?->format('Y-m'))
            ->filter()
            ->unique()
            ->count();

        $projected = $months > 0 ? round($ytdBasic / $months, 2) : 0.0;

        return [
            'year' => $year,
            'months_covered' => $months,
            'ytd_basic' => $ytdBasic,
            'accrued' => round($ytdBasic / 12, 2),
            'projected' => $projected,
            'tax_exempt_cap' => self::TAX_EXEMPT_CAP,
            'taxable_excess' => round(max(0.0, $projected - self::TAX_EXEMPT_CAP), 2),
        ];
    }
}
