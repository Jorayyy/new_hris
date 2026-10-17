<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeAllowance;
use App\Models\EmployeeDeduction;
use App\Models\EmployeeLoan;
use App\Models\LoanPayment;
use App\Models\LoanType;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Models\User;
use App\Services\ThirteenthMonthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SelfServicePayTest extends TestCase
{
    use RefreshDatabase;

    private Branch $hq;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hq = Branch::create(['name' => 'Tacloban HQ', 'code' => 'TAC']);
    }

    private function user(string $role, string $email): User
    {
        return User::create([
            'name' => ucfirst($role), 'email' => $email, 'password' => 'password',
            'role' => $role, 'is_active' => true, 'branch_id' => $this->hq->id,
        ]);
    }

    private function employee(?User $user = null): Employee
    {
        return Employee::create([
            'employee_no' => 'E-001', 'first_name' => 'Juan', 'last_name' => 'Cruz',
            'branch_id' => $this->hq->id, 'user_id' => $user?->id, 'is_active' => true,
            'basic_salary' => 20000,
        ]);
    }

    private function payslip(Employee $employee, string $dateTo, float $basic): Payslip
    {
        $period = PayrollPeriod::create([
            'name' => 'Period '.$dateTo,
            'date_from' => substr($dateTo, 0, 8).'01',
            'date_to' => $dateTo,
            'pay_date' => $dateTo,
            'frequency' => 'semi_monthly',
            'status' => 'closed',
        ]);

        $run = PayrollRun::create(['payroll_period_id' => $period->id, 'status' => 'finalized']);

        return Payslip::create([
            'payroll_run_id' => $run->id, 'employee_id' => $employee->id,
            'basic_pay' => $basic, 'gross_pay' => $basic, 'total_deductions' => 0, 'net_pay' => $basic,
        ]);
    }

    public function test_thirteenth_month_summary_uses_year_to_date_basic(): void
    {
        $employee = $this->employee();
        $year = now()->year;

        $this->payslip($employee, $year.'-01-15', 10000);
        $this->payslip($employee, $year.'-01-31', 10000);
        $this->payslip($employee, $year.'-02-15', 10000);

        $summary = (new ThirteenthMonthService)->summary($employee);

        $this->assertSame(30000.0, $summary['ytd_basic']);
        $this->assertSame(2, $summary['months_covered']);
        $this->assertSame(2500.0, $summary['accrued']);
        $this->assertSame(15000.0, $summary['projected']);
        $this->assertSame(0.0, $summary['taxable_excess']);
    }

    public function test_thirteenth_month_summary_is_empty_without_payslips(): void
    {
        $summary = (new ThirteenthMonthService)->summary($this->employee());

        $this->assertSame(0.0, $summary['ytd_basic']);
        $this->assertSame(0.0, $summary['projected']);
        $this->assertSame(0.0, $summary['taxable_excess']);
    }

    public function test_my_pay_page_shows_loans_allowances_and_deductions(): void
    {
        $user = $this->user(User::ROLE_EMPLOYEE, 'emp@test.local');
        $employee = $this->employee($user);

        $type = LoanType::create(['name' => 'SSS Salary Loan', 'interest_rate' => 0.1, 'is_government' => true]);
        $loan = EmployeeLoan::create([
            'employee_id' => $employee->id, 'loan_type_id' => $type->id,
            'principal' => 12000, 'monthly_amortization' => 1000, 'balance' => 12000,
            'status' => EmployeeLoan::STATUS_ACTIVE,
        ]);
        LoanPayment::create(['employee_loan_id' => $loan->id, 'amount' => 1000, 'paid_on' => now()->toDateString()]);
        $loan->update(['balance' => 11000]);

        EmployeeAllowance::create(['employee_id' => $employee->id, 'name' => 'Rice allowance', 'amount' => 1500, 'is_taxable' => false]);
        EmployeeDeduction::create(['employee_id' => $employee->id, 'name' => 'Uniform deduction', 'amount' => 250]);

        $this->actingAs($user)->get(route('me.pay'))
            ->assertOk()
            ->assertSee('SSS Salary Loan')
            ->assertSee('11,000.00')
            ->assertSee('Rice allowance')
            ->assertSee('Uniform deduction');
    }

    public function test_employee_without_a_record_gets_a_404(): void
    {
        $user = $this->user(User::ROLE_EMPLOYEE, 'emp@test.local');

        $this->actingAs($user)->get(route('me.pay'))->assertNotFound();
    }

    public function test_hr_cannot_open_employee_self_service(): void
    {
        $this->actingAs($this->user(User::ROLE_HR, 'hr@test.local'))->get(route('me.pay'))->assertForbidden();
    }
}
