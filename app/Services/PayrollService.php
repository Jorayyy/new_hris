<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\Payslip;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Builds payslips for a payroll run. Uses DtrService for attendance figures,
 * PremiumPayService for DOLE premium/overtime/night-differential pay, and
 * PhContributionService for statutory contributions and tax.
 */
class PayrollService
{
    public function __construct(
        private readonly DtrService $dtr = new DtrService,
        private readonly PhContributionService $ph = new PhContributionService,
        private readonly PremiumPayService $premium = new PremiumPayService,
        private readonly LoanService $loans = new LoanService,
    ) {}

    public function processRun(PayrollRun $run): PayrollRun
    {
        $period = $run->period;

        abort_if($period->status === 'closed', 403, 'This pay period is closed and cannot be processed.');

        $divisor = PhContributionService::periodsPerMonth($period->frequency ?? 'semi_monthly');

        $employees = Employee::query()
            ->where('is_active', true)
            ->when($run->branch_id, fn ($q) => $q->where('branch_id', $run->branch_id))
            ->with(['allowances', 'deductions'])
            ->get();

        return DB::transaction(function () use ($run, $period, $employees, $divisor) {
            $this->loans->clearRun($run);

            foreach ($employees as $employee) {
                $data = $this->computePayslip($employee, $period);
                $payslip = Payslip::updateOrCreate(
                    ['payroll_run_id' => $run->id, 'employee_id' => $employee->id],
                    $data['totals'] + ['status' => 'final']
                );
                $payslip->items()->delete();
                foreach ($data['items'] as $item) {
                    $payslip->items()->create($item);
                }

                $this->loans->applyForRun($employee, $run, $divisor);
            }

            $run->update([
                'status' => 'processed',
                'employee_count' => $employees->count(),
                'gross_total' => $run->payslips()->sum('gross_pay'),
                'deductions_total' => $run->payslips()->sum('total_deductions'),
                'net_total' => $run->payslips()->sum('net_pay'),
                'processed_by' => auth()->id(),
                'processed_at' => now(),
                'approved_by' => null,
                'approved_at' => null,
                'voided_by' => null,
                'voided_at' => null,
                'void_reason' => null,
            ]);

            $period->update(['status' => 'processed']);

            return $run->fresh(['payslips.employee', 'period', 'branch']);
        });
    }

    public function deleteRun(PayrollRun $run): void
    {
        $this->loans->clearRun($run);
        $run->delete();
    }

    /** Void a run: reverse its loan postings and flag it for reprocessing. */
    public function voidRun(PayrollRun $run, string $reason, ?int $userId = null): void
    {
        DB::transaction(function () use ($run, $reason, $userId) {
            $this->loans->clearRun($run);

            $run->update([
                'status' => PayrollRun::STATUS_VOIDED,
                'voided_by' => $userId,
                'voided_at' => now(),
                'void_reason' => $reason,
            ]);
        });
    }

    public function computePayslip(Employee $employee, PayrollPeriod $period): array
    {
        $frequency = $period->frequency ?? 'semi_monthly';
        $divisor = PhContributionService::periodsPerMonth($frequency);

        $from = $period->date_from->toDateString();
        $to = $period->date_to->toDateString();

        $summary = $this->dtr->summarize($employee, $from, $to);
        $daily = $employee->dailyRate();
        $hourly = $employee->hourlyRate();

        $premium = $this->premium->summarize(
            Attendance::query()->where('employee_id', $employee->id)->workDateBetween($from, $to)->get(),
            $this->periodHolidays($employee, $period),
            array_map('intval', $employee->rest_days ?? []),
            $daily,
            $hourly,
        );

        $basicPay = $premium['basic'];
        $restDayPay = $premium['rest_day'];
        $holidayPay = $premium['holiday'];
        $overtimePay = $premium['overtime'];
        $nightDiffPay = $premium['night_diff'];
        $leavePay = $this->leavePay($employee, $from, $to, $daily, $divisor);

        $allowances = $employee->allowances;
        $allowancesTotal = round($allowances->sum(fn ($a) => (float) $a->amount) / $divisor, 2);
        $nonTaxableAllowances = round($allowances->where('is_taxable', false)->sum(fn ($a) => (float) $a->amount) / $divisor, 2);

        $grossPay = round($basicPay + $restDayPay + $holidayPay + $overtimePay + $nightDiffPay + $leavePay + $allowancesTotal, 2);

        $statutory = $this->ph->employeePeriodShares($employee, $frequency);
        $lateUndertime = round(($summary['late_minutes'] + $summary['undertime_minutes']) / 60 * $hourly, 2);

        $otherDeductions = round($employee->deductions->sum(fn ($d) => (float) $d->amount) / $divisor, 2);
        $loanDeductions = $this->loans->totalFor($employee, $divisor);

        $taxableIncome = max(0, $grossPay - $statutory['sss'] - $statutory['philhealth'] - $statutory['pagibig'] - $nonTaxableAllowances - $lateUndertime);
        $tax = $this->ph->withholdingTax($taxableIncome, $frequency);

        $totalDeductions = round($statutory['sss'] + $statutory['philhealth'] + $statutory['pagibig'] + $tax + $lateUndertime + $otherDeductions + $loanDeductions, 2);
        $netPay = round($grossPay - $totalDeductions, 2);

        $items = [];
        $earnings = [
            'Basic pay' => $basicPay,
            'Rest day premium' => $restDayPay,
            'Overtime pay' => $overtimePay,
            'Night differential' => $nightDiffPay,
            'Holiday pay' => $holidayPay,
            'Leave pay' => $leavePay,
            'Allowances' => $allowancesTotal,
        ];
        foreach ($earnings as $label => $amount) {
            if ($amount == 0.0) {
                continue;
            }
            $items[] = ['type' => 'earning', 'label' => $label, 'amount' => $amount, 'is_taxable' => $label !== 'Allowances', 'is_statutory' => false];
        }

        $deductions = [
            'SSS' => $statutory['sss'],
            'PhilHealth' => $statutory['philhealth'],
            'Pag-IBIG' => $statutory['pagibig'],
            'Withholding tax' => $tax,
            'Late / undertime' => $lateUndertime,
            'Other deductions' => $otherDeductions,
            'Loan payments' => $loanDeductions,
        ];
        foreach ($deductions as $label => $amount) {
            if ($amount == 0.0) {
                continue;
            }
            $items[] = ['type' => 'deduction', 'label' => $label, 'amount' => $amount, 'is_taxable' => false, 'is_statutory' => in_array($label, ['SSS', 'PhilHealth', 'Pag-IBIG', 'Withholding tax'], true)];
        }

        return [
            'totals' => [
                'days_worked' => $summary['days_worked'],
                'hours_worked' => round($summary['hours_worked'], 2),
                'basic_pay' => $basicPay,
                'rest_day_pay' => $restDayPay,
                'overtime_pay' => $overtimePay,
                'night_diff_pay' => $nightDiffPay,
                'holiday_pay' => $holidayPay,
                'leave_pay' => $leavePay,
                'allowances_total' => $allowancesTotal,
                'gross_pay' => $grossPay,
                'sss_contribution' => $statutory['sss'],
                'philhealth_contribution' => $statutory['philhealth'],
                'pagibig_contribution' => $statutory['pagibig'],
                'tax_withheld' => $tax,
                'late_undertime_deduction' => $lateUndertime,
                'other_deductions_total' => $otherDeductions,
                'loan_deductions_total' => $loanDeductions,
                'total_deductions' => $totalDeductions,
                'net_pay' => $netPay,
            ],
            'items' => $items,
        ];
    }

    /** @return array<string, string> date (Y-m-d) => holiday type, scoped to the employee's branch */
    private function periodHolidays(Employee $employee, PayrollPeriod $period): array
    {
        return Holiday::query()
            ->whereBetween('date', [$period->date_from->toDateString(), $period->date_to->toDateString()])
            ->where(fn ($q) => $q->whereNull('branch_id')->orWhere('branch_id', $employee->branch_id))
            ->get()
            ->mapWithKeys(fn ($holiday) => [$holiday->date->toDateString() => $holiday->type])
            ->all();
    }

    private function leavePay(Employee $employee, string $from, string $to, float $daily, int $divisor): float
    {
        $paidDays = LeaveRequest::query()
            ->where('employee_id', $employee->id)
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->where('is_paid', true)
            ->where('date_from', '<=', $to)
            ->where('date_to', '>=', $from)
            ->sum('days');

        return round(((float) $paidDays) * $daily, 2);
    }

    /** 13th month pay = total basic salary earned in a calendar year / 12. */
    public function thirteenthMonth(Employee $employee, int $year): float
    {
        return $this->thirteenthMonthTotals($year)->get($employee->id, 0.0);
    }

    /** @return Collection<int|string, float> employee id => 13th month amount */
    public function thirteenthMonthTotals(int $year): Collection
    {
        return Payslip::query()
            ->selectRaw('employee_id, SUM(basic_pay) as basic')
            ->whereHas('run.period', fn ($q) => $q->whereYear('date_to', $year))
            ->groupBy('employee_id')
            ->pluck('basic', 'employee_id')
            ->map(fn ($basic) => round(((float) $basic) / 12, 2));
    }
}
