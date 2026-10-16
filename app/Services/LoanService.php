<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\LoanPayment;
use App\Models\PayrollRun;

/**
 * Employee loan amortization. The loan balance is always derived from
 * principal minus the sum of its payments, so posting a payroll run is
 * idempotent: re-running a run replaces its payment rather than double-charging.
 */
class LoanService
{
    public function periodDeduction(EmployeeLoan $loan, int $divisor): float
    {
        $perPeriod = (float) $loan->monthly_amortization / max($divisor, 1);

        return round(min($perPeriod, (float) $loan->balance), 2);
    }

    /** @return array<int, array{loan: EmployeeLoan, amount: float}> */
    public function deductionsFor(Employee $employee, int $divisor): array
    {
        return $employee->loans()->active()->get()
            ->map(fn (EmployeeLoan $loan) => ['loan' => $loan, 'amount' => $this->periodDeduction($loan, $divisor)])
            ->filter(fn ($row) => $row['amount'] > 0)
            ->values()
            ->all();
    }

    public function totalFor(Employee $employee, int $divisor): float
    {
        return round(array_sum(array_column($this->deductionsFor($employee, $divisor), 'amount')), 2);
    }

    /** Drop any payments already posted for this run (used before reprocessing). */
    public function clearRun(PayrollRun $run): void
    {
        $loanIds = LoanPayment::where('payroll_run_id', $run->id)->pluck('employee_loan_id')->unique();

        LoanPayment::where('payroll_run_id', $run->id)->delete();

        foreach (EmployeeLoan::whereIn('id', $loanIds)->get() as $loan) {
            $this->recalculate($loan);
        }
    }

    public function applyForRun(Employee $employee, PayrollRun $run, int $divisor): float
    {
        $total = 0.0;

        foreach ($employee->loans()->active()->get() as $loan) {
            $amount = $this->periodDeduction($loan, $divisor);
            if ($amount <= 0) {
                continue;
            }

            LoanPayment::updateOrCreate(
                ['employee_loan_id' => $loan->id, 'payroll_run_id' => $run->id],
                ['amount' => $amount, 'paid_on' => now()->toDateString()],
            );

            $this->recalculate($loan);
            $total += $amount;
        }

        return round($total, 2);
    }

    public function recalculate(EmployeeLoan $loan): void
    {
        $paid = (float) $loan->payments()->sum('amount');

        $loan->balance = round(max(0.0, (float) $loan->principal - $paid), 2);
        $loan->status = $loan->balance <= 0 ? EmployeeLoan::STATUS_COMPLETED : EmployeeLoan::STATUS_ACTIVE;
        $loan->save();
    }
}
