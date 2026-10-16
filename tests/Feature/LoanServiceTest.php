<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\LoanType;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Services\LoanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanServiceTest extends TestCase
{
    use RefreshDatabase;

    private function loan(float $principal, float $monthly): EmployeeLoan
    {
        $employee = Employee::create([
            'employee_no' => 'EMP0001', 'first_name' => 'Test', 'last_name' => 'Employee',
            'basic_salary' => 22000, 'tax_status' => 'S', 'employment_status' => 'regular', 'is_active' => true,
        ]);
        $type = LoanType::create(['name' => 'Company Loan', 'interest_rate' => 0, 'is_active' => true]);

        return EmployeeLoan::create([
            'employee_id' => $employee->id, 'loan_type_id' => $type->id,
            'principal' => $principal, 'monthly_amortization' => $monthly,
            'balance' => $principal, 'status' => EmployeeLoan::STATUS_ACTIVE,
        ]);
    }

    private function makeRun(): PayrollRun
    {
        $period = PayrollPeriod::create([
            'name' => 'Jan 1-15', 'date_from' => '2026-01-01', 'date_to' => '2026-01-15',
            'pay_date' => '2026-01-20', 'frequency' => 'semi_monthly', 'status' => 'open',
        ]);

        return PayrollRun::create(['payroll_period_id' => $period->id, 'status' => 'draft']);
    }

    public function test_period_deduction_splits_monthly_amortization(): void
    {
        $loan = $this->loan(6000, 1000);

        $this->assertSame(500.0, (new LoanService)->periodDeduction($loan, 2));
    }

    public function test_applying_a_run_reduces_balance(): void
    {
        $loan = $this->loan(6000, 1000);
        $run = $this->makeRun();

        (new LoanService)->applyForRun($loan->employee, $run, 2);

        $this->assertSame(5500.0, (float) $loan->fresh()->balance);
        $this->assertSame(1, $loan->payments()->count());
    }

    public function test_applying_the_same_run_twice_is_idempotent(): void
    {
        $loan = $this->loan(6000, 1000);
        $run = $this->makeRun();
        $service = new LoanService;

        $service->applyForRun($loan->employee, $run, 2);
        $service->applyForRun($loan->employee, $run, 2);

        $this->assertSame(5500.0, (float) $loan->fresh()->balance);
        $this->assertSame(1, $loan->payments()->count());
    }

    public function test_loan_completes_when_balance_reaches_zero(): void
    {
        $loan = $this->loan(500, 1000);

        (new LoanService)->applyForRun($loan->employee, $this->makeRun(), 1);

        $loan->refresh();
        $this->assertSame(0.0, (float) $loan->balance);
        $this->assertSame(EmployeeLoan::STATUS_COMPLETED, $loan->status);
    }

    public function test_clear_run_removes_postings_and_restores_balance(): void
    {
        $loan = $this->loan(6000, 1000);
        $run = $this->makeRun();
        $service = new LoanService;

        $service->applyForRun($loan->employee, $run, 2);
        $service->clearRun($run);

        $this->assertSame(0, $loan->payments()->count());
        $this->assertSame(6000.0, (float) $loan->fresh()->balance);
    }
}
