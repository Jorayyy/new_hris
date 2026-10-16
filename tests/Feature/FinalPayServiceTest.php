<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeDeduction;
use App\Models\EmployeeLoan;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\LoanType;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Services\FinalPayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinalPayServiceTest extends TestCase
{
    use RefreshDatabase;

    private function employee(array $overrides = []): Employee
    {
        return Employee::create(array_merge([
            'employee_no' => 'EMP0001', 'first_name' => 'Test', 'last_name' => 'Employee',
            'basic_salary' => 22000, 'daily_rate' => 1000, 'date_hired' => '2026-01-01',
            'tax_status' => 'S', 'employment_status' => 'regular', 'is_active' => true,
        ], $overrides));
    }

    private function present(Employee $employee, string $date): void
    {
        Attendance::create([
            'employee_id' => $employee->id, 'work_date' => $date,
            'hours_worked' => 8, 'status' => Attendance::STATUS_PRESENT,
        ]);
    }

    public function test_final_pay_sums_components(): void
    {
        $employee = $this->employee();
        foreach (['2026-01-01', '2026-01-02', '2026-01-05', '2026-01-06', '2026-01-07'] as $date) {
            $this->present($employee, $date);
        }

        $type = LeaveType::create(['name' => 'Vacation', 'code' => 'VL', 'is_paid' => true, 'is_cash_convertible' => true, 'pay_percentage' => 100, 'max_days_per_year' => 15, 'is_active' => true]);
        LeaveBalance::create(['employee_id' => $employee->id, 'leave_type_id' => $type->id, 'year' => 2026, 'credits' => 5, 'used' => 2]);

        $loanType = LoanType::create(['name' => 'Company Loan', 'interest_rate' => 0, 'is_active' => true]);
        EmployeeLoan::create(['employee_id' => $employee->id, 'loan_type_id' => $loanType->id, 'principal' => 2000, 'monthly_amortization' => 500, 'balance' => 2000, 'status' => EmployeeLoan::STATUS_ACTIVE]);

        EmployeeDeduction::create(['employee_id' => $employee->id, 'name' => 'HMO', 'amount' => 100, 'is_recurring' => true]);

        $result = (new FinalPayService)->compute($employee->fresh(), '2026-01-10');

        $this->assertSame(5, $result['present_days']);
        $this->assertSame(5000.0, $result['basic_pay']);
        $this->assertSame(round(5000 / 12, 2), $result['thirteenth_month']);
        $this->assertSame(3.0, $result['leave_days']);
        $this->assertSame(3000.0, $result['leave_conversion']);
        $this->assertSame(2000.0, $result['loan_balance']);
        $this->assertSame(100.0, $result['other_deductions']);
        $this->assertSame(round(5000 + round(5000 / 12, 2) + 3000 - 2000 - 100, 2), $result['net_pay']);
    }

    public function test_days_already_paid_are_excluded(): void
    {
        $employee = $this->employee();

        $period = PayrollPeriod::create([
            'name' => 'Jan 1-5', 'date_from' => '2026-01-01', 'date_to' => '2026-01-05',
            'pay_date' => '2026-01-05', 'frequency' => 'semi_monthly', 'status' => 'processed',
        ]);
        $run = PayrollRun::create(['payroll_period_id' => $period->id, 'status' => 'processed']);
        Payslip::create(['payroll_run_id' => $run->id, 'employee_id' => $employee->id, 'basic_pay' => 4000, 'status' => 'final']);

        $this->present($employee, '2026-01-03');
        $this->present($employee, '2026-01-08');

        $result = (new FinalPayService)->compute($employee->fresh(), '2026-01-10');

        $this->assertSame('2026-01-06', $result['period_start']);
        $this->assertSame(1, $result['present_days']);
        $this->assertSame(1000.0, $result['basic_pay']);
    }
}
