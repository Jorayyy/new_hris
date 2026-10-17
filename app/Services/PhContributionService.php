<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\PagibigContribution;
use App\Models\PhilhealthContribution;
use App\Models\SssContribution;
use App\Models\TaxTable;
use Illuminate\Support\Collection;

/**
 * Philippine statutory contributions and BIR withholding tax.
 * Tables are stored in the DB (seeded) so rates can be updated without code changes.
 */
class PhContributionService
{
    /** Tables are tiny and read once per request/payroll run instead of per employee. */
    private ?Collection $sssRows = null;

    private ?Collection $philhealthRows = null;

    private ?Collection $pagibigRows = null;

    private ?Collection $taxRows = null;

    public function sssMonthly(float $monthlySalary): array
    {
        $rows = $this->sssRows ??= SssContribution::query()->orderBy('min_msc')->get();

        $row = $rows->first(fn (SssContribution $r) => (float) $r->min_msc <= $monthlySalary && (float) $r->max_msc >= $monthlySalary)
            ?? $rows->sortByDesc('max_msc')->first();

        return [
            'msc' => (float) ($row?->max_msc ?? 0),
            'employee' => (float) ($row?->employee_share ?? 0),
            'employer' => (float) ($row?->employer_share ?? 0),
            'ec' => (float) ($row?->ec_share ?? 0),
        ];
    }

    public function philhealthMonthly(float $monthlySalary, ?int $year = null): array
    {
        $year ??= (int) date('Y');
        $rows = $this->philhealthRows ??= PhilhealthContribution::query()->get();
        $row = $rows->firstWhere('year', $year) ?? $rows->sortByDesc('year')->first();

        if (! $row) {
            return ['employee' => 0.0, 'employer' => 0.0, 'total' => 0.0, 'basis' => 0.0];
        }

        $basis = min(max($monthlySalary, (float) $row->min_salary), (float) $row->max_salary);

        return [
            'basis' => $basis,
            'employee' => round($basis * (float) $row->employee_rate, 2),
            'employer' => round($basis * (float) $row->employer_rate, 2),
            'total' => round($basis * ((float) $row->employee_rate + (float) $row->employer_rate), 2),
        ];
    }

    public function pagibigMonthly(float $monthlySalary): array
    {
        $rows = $this->pagibigRows ??= PagibigContribution::query()->orderBy('min_salary')->get();

        $row = $rows->first(fn (PagibigContribution $r) => (float) $r->min_salary <= $monthlySalary
                && ($r->max_salary === null || (float) $r->max_salary >= $monthlySalary))
            ?? $rows->first();

        if (! $row) {
            return ['employee' => 0.0, 'employer' => 0.0];
        }

        return [
            'employee' => round(min($monthlySalary * (float) $row->employee_rate, (float) $row->employee_cap), 2),
            'employer' => round(min($monthlySalary * (float) $row->employer_rate, (float) $row->employer_cap), 2),
        ];
    }

    /**
     * BIR withholding tax on taxable compensation for the period.
     */
    public function withholdingTax(float $taxableIncome, string $period = 'semi_monthly', ?int $year = null): float
    {
        if ($taxableIncome <= 0) {
            return 0.0;
        }

        $year ??= (int) date('Y');
        $rows = $this->taxRows ??= TaxTable::query()->get();

        $row = $rows->first(fn (TaxTable $r) => (int) $r->year === $year
            && $r->period === $period
            && (float) $r->min_taxable <= $taxableIncome
            && ($r->max_taxable === null || (float) $r->max_taxable >= $taxableIncome));

        if (! $row) {
            return 0.0;
        }

        $tax = (float) $row->base_tax + max(0, $taxableIncome - (float) $row->excess_over) * (float) $row->rate;

        return round($tax, 2);
    }

    /**
     * Employee-side statutory contributions for one payroll period.
     * Monthly figures are split across periods_per_month (2 for semi-monthly).
     */
    public function employeePeriodShares(Employee $employee, string $frequency = 'semi_monthly', ?int $year = null): array
    {
        $monthly = (float) $employee->basic_salary;
        $divisor = $frequency === 'monthly' ? 1 : 2;

        $sss = $this->sssMonthly($monthly);
        $ph = $this->philhealthMonthly($monthly, $year);
        $pi = $this->pagibigMonthly($monthly);

        return [
            'sss' => round($sss['employee'] / $divisor, 2),
            'philhealth' => round($ph['employee'] / $divisor, 2),
            'pagibig' => round($pi['employee'] / $divisor, 2),
            'sss_employer' => round($sss['employer'] / $divisor, 2),
            'philhealth_employer' => round($ph['employer'] / $divisor, 2),
            'pagibig_employer' => round($pi['employer'] / $divisor, 2),
        ];
    }
}
