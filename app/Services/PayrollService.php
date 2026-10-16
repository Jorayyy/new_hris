<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\Payslip;
use Illuminate\Support\Facades\DB;

/**
 * Builds payslips for a payroll run. Uses DtrService for attendance figures and
 * PhContributionService for statutory contributions and tax.
 *
 * Simplifications (documented on purpose): OT is flat 125%, night differential 10%,
 * holiday premium = multiplier - 1 on top of basic, 13th month = basic pay / 12.
 */
class PayrollService
{
    public const OVERTIME_RATE = 1.25;

    public const NIGHT_DIFF_RATE = 0.10;

    public function __construct(
        private readonly DtrService $dtr = new DtrService,
        private readonly PhContributionService $ph = new PhContributionService,
    ) {}

    public function processRun(PayrollRun $run): PayrollRun
    {
        $period = $run->period;

        abort_if($period->status === 'closed', 403, 'This pay period is closed and cannot be processed.');

        $employees = Employee::query()
            ->where('is_active', true)
            ->when($run->branch_id, fn ($q) => $q->where('branch_id', $run->branch_id))
            ->with(['allowances', 'deductions'])
            ->get();

        return DB::transaction(function () use ($run, $period, $employees) {
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
            }

            $run->update([
                'status' => 'processed',
                'employee_count' => $employees->count(),
                'gross_total' => $run->payslips()->sum('gross_pay'),
                'deductions_total' => $run->payslips()->sum('total_deductions'),
                'net_total' => $run->payslips()->sum('net_pay'),
                'processed_by' => auth()->id(),
                'processed_at' => now(),
            ]);

            $period->update(['status' => 'processed']);

            return $run->fresh(['payslips.employee', 'period', 'branch']);
        });
    }

    public function deleteRun(PayrollRun $run): void
    {
        $run->delete();
    }

    public function computePayslip(Employee $employee, PayrollPeriod $period): array
    {
        $frequency = $period->frequency ?? 'semi_monthly';
        $divisor = $frequency === 'monthly' ? 1 : 2;

        $from = $period->date_from->toDateString();
        $to = $period->date_to->toDateString();

        $summary = $this->dtr->summarize($employee, $from, $to);
        $daily = $employee->dailyRate();
        $hourly = $employee->hourlyRate();

        $basicPay = round($daily * $summary['days_worked'], 2);
        $overtimePay = round($summary['overtime_hours'] * $hourly * self::OVERTIME_RATE, 2);
        $nightDiffPay = round($summary['night_diff_hours'] * $hourly * self::NIGHT_DIFF_RATE, 2);
        $holidayPay = $this->holidayPay($employee, $period, $daily);
        $leavePay = $this->leavePay($employee, $from, $to, $daily, $divisor);

        $allowances = $employee->allowances;
        $allowancesTotal = round($allowances->sum(fn ($a) => (float) $a->amount) / $divisor, 2);
        $nonTaxableAllowances = round($allowances->where('is_taxable', false)->sum(fn ($a) => (float) $a->amount) / $divisor, 2);

        $grossPay = round($basicPay + $overtimePay + $nightDiffPay + $holidayPay + $leavePay + $allowancesTotal, 2);

        $statutory = $this->ph->employeePeriodShares($employee, $frequency);
        $lateUndertime = round(($summary['late_minutes'] + $summary['undertime_minutes']) / 60 * $hourly, 2);

        $otherDeductions = round($employee->deductions->sum(fn ($d) => (float) $d->amount) / $divisor, 2);

        $taxableIncome = max(0, $grossPay - $statutory['sss'] - $statutory['philhealth'] - $statutory['pagibig'] - $nonTaxableAllowances - $lateUndertime);
        $tax = $this->ph->withholdingTax($taxableIncome, $frequency);

        $totalDeductions = round($statutory['sss'] + $statutory['philhealth'] + $statutory['pagibig'] + $tax + $lateUndertime + $otherDeductions, 2);
        $netPay = round($grossPay - $totalDeductions, 2);

        $items = [];
        $earnings = [
            'Basic pay' => $basicPay,
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
                'total_deductions' => $totalDeductions,
                'net_pay' => $netPay,
            ],
            'items' => $items,
        ];
    }

    private function holidayPay(Employee $employee, PayrollPeriod $period, float $daily): float
    {
        $holidays = Holiday::query()
            ->whereBetween('date', [$period->date_from->toDateString(), $period->date_to->toDateString()])
            ->where(fn ($q) => $q->whereNull('branch_id')->orWhere('branch_id', $employee->branch_id))
            ->get();

        $pay = 0.0;
        foreach ($holidays as $holiday) {
            $worked = Attendance::query()
                ->where('employee_id', $employee->id)
                ->whereDate('work_date', $holiday->date)
                ->where('status', Attendance::STATUS_PRESENT)
                ->exists();

            $multiplier = (float) $holiday->rate_multiplier;
            if ($worked) {
                $pay += ($multiplier - 1) * $daily;
            } elseif ($holiday->type === 'regular') {
                $pay += $daily;
            }
        }

        return round($pay, 2);
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
        $total = Payslip::query()
            ->where('employee_id', $employee->id)
            ->whereHas('run.period', fn ($q) => $q->whereYear('date_to', $year))
            ->sum('basic_pay');

        return round(((float) $total) / 12, 2);
    }
}
