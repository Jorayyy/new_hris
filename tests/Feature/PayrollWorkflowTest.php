<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\LoanType;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\User;
use Database\Seeders\GovernmentTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(GovernmentTableSeeder::class);
    }

    private function user(string $email): User
    {
        return User::create([
            'name' => 'User', 'email' => $email,
            'password' => 'password', 'role' => User::ROLE_PAYROLL, 'is_active' => true,
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

    private function employee(): Employee
    {
        return Employee::create([
            'employee_no' => 'EMP0001', 'first_name' => 'Test', 'last_name' => 'Employee',
            'basic_salary' => 22000, 'tax_status' => 'S', 'employment_status' => 'regular',
            'bank_name' => 'BPI', 'bank_account_no' => '1234567890', 'is_active' => true,
        ]);
    }

    public function test_maker_processes_and_checker_approves(): void
    {
        $maker = $this->user('maker@test.local');
        $checker = $this->user('checker@test.local');
        $run = $this->makeRun();

        $this->actingAs($maker)->post(route('payroll.runs.process', $run))->assertRedirect();
        $this->assertSame(PayrollRun::STATUS_PROCESSED, $run->fresh()->status);

        $this->actingAs($checker)->post(route('payroll.runs.approve', $run))->assertRedirect();
        $run->refresh();
        $this->assertSame(PayrollRun::STATUS_APPROVED, $run->status);
        $this->assertSame($checker->id, $run->approved_by);
    }

    public function test_maker_cannot_approve_own_run(): void
    {
        $maker = $this->user('maker@test.local');
        $run = $this->makeRun();

        $this->actingAs($maker)->post(route('payroll.runs.process', $run))->assertRedirect();
        $this->actingAs($maker)->post(route('payroll.runs.approve', $run))->assertForbidden();
    }

    public function test_voiding_reverses_loan_postings(): void
    {
        $maker = $this->user('maker@test.local');
        $employee = $this->employee();
        $type = LoanType::create(['name' => 'Company Loan', 'interest_rate' => 0, 'is_active' => true]);
        $loan = EmployeeLoan::create([
            'employee_id' => $employee->id, 'loan_type_id' => $type->id,
            'principal' => 6000, 'monthly_amortization' => 1000, 'balance' => 6000,
            'status' => EmployeeLoan::STATUS_ACTIVE,
        ]);
        Attendance::create(['employee_id' => $employee->id, 'work_date' => '2026-01-05', 'hours_worked' => 8, 'status' => Attendance::STATUS_PRESENT]);

        $run = $this->makeRun();
        $this->actingAs($maker)->post(route('payroll.runs.process', $run))->assertRedirect();
        $this->assertSame(1, $loan->payments()->count());

        $this->actingAs($maker)->post(route('payroll.runs.void', $run), ['void_reason' => 'Wrong period'])
            ->assertRedirect();

        $run->refresh();
        $this->assertSame(PayrollRun::STATUS_VOIDED, $run->status);
        $this->assertSame('Wrong period', $run->void_reason);
        $this->assertSame(0, $loan->payments()->count());
        $this->assertSame(6000.0, (float) $loan->fresh()->balance);
    }

    public function test_bank_file_requires_approval(): void
    {
        $maker = $this->user('maker@test.local');
        $checker = $this->user('checker@test.local');
        $run = $this->makeRun();
        $this->employee();

        $this->actingAs($maker)->post(route('payroll.runs.process', $run));
        $this->actingAs($maker)->get(route('payroll.runs.bank-file', $run))->assertForbidden();

        $this->actingAs($checker)->post(route('payroll.runs.approve', $run));

        $this->actingAs($checker)->get(route('payroll.runs.bank-file', $run))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=utf-8')
            ->assertSee('1234567890');
    }

    public function test_voided_run_can_be_reprocessed(): void
    {
        $maker = $this->user('maker@test.local');
        $run = $this->makeRun();

        $this->actingAs($maker)->post(route('payroll.runs.process', $run));
        $this->actingAs($maker)->post(route('payroll.runs.void', $run), ['void_reason' => 'Fix']);
        $this->actingAs($maker)->post(route('payroll.runs.process', $run))->assertRedirect();

        $this->assertSame(PayrollRun::STATUS_PROCESSED, $run->fresh()->status);
    }
}
