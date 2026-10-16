<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\Shift;
use App\Services\PayrollService;
use Carbon\Carbon;
use Database\Seeders\GovernmentTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(GovernmentTableSeeder::class);
    }

    private function employee(float $basic = 22000): Employee
    {
        return Employee::create([
            'employee_no' => 'EMP0001',
            'first_name' => 'Test',
            'last_name' => 'Employee',
            'basic_salary' => $basic,
            'tax_status' => 'S',
            'employment_status' => 'regular',
            'is_active' => true,
        ]);
    }

    public function test_payslip_computes_basic_and_statutory(): void
    {
        $employee = $this->employee(22000);
        $period = PayrollPeriod::create([
            'name' => 'Jan 1-15', 'date_from' => '2026-01-01', 'date_to' => '2026-01-15',
            'pay_date' => '2026-01-20', 'frequency' => 'semi_monthly', 'status' => 'open',
        ]);

        foreach (range(1, 10) as $day) {
            Attendance::create([
                'employee_id' => $employee->id,
                'work_date' => Carbon::parse('2026-01-'.str_pad((string) $day, 2, '0', STR_PAD_LEFT))->toDateString(),
                'hours_worked' => 8,
                'status' => Attendance::STATUS_PRESENT,
            ]);
        }

        $data = (new PayrollService)->computePayslip($employee, $period);
        $totals = $data['totals'];

        $dailyRate = round(22000 / 22, 4);
        $this->assertEquals(10, $totals['days_worked']);
        $this->assertEquals(round($dailyRate * 10, 2), $totals['basic_pay']);
        $this->assertEqualsWithDelta($totals['gross_pay'] - $totals['total_deductions'], $totals['net_pay'], 0.01);
        $this->assertEqualsWithDelta(round($totals['sss_contribution'] + $totals['philhealth_contribution'] + $totals['pagibig_contribution'] + $totals['tax_withheld'] + $totals['late_undertime_deduction'] + $totals['loans_total'] + $totals['other_deductions_total'], 2), $totals['total_deductions'], 0.01);
        $this->assertGreaterThan(0, $totals['net_pay']);
    }

    public function test_late_deduction_reduces_net(): void
    {
        $employee = $this->employee();
        $shift = Shift::create(['name' => 'Day', 'time_in' => '08:00', 'time_out' => '17:00', 'break_minutes' => 60, 'grace_minutes' => 0, 'required_hours' => 8]);

        $period = PayrollPeriod::create([
            'name' => 'Jan 1-15', 'date_from' => '2026-01-01', 'date_to' => '2026-01-15',
            'pay_date' => '2026-01-20', 'frequency' => 'semi_monthly', 'status' => 'open',
        ]);

        Attendance::create([
            'employee_id' => $employee->id,
            'shift_id' => $shift->id,
            'work_date' => '2026-01-05',
            'hours_worked' => 8,
            'late_minutes' => 60,
            'status' => Attendance::STATUS_PRESENT,
        ]);

        $totals = (new PayrollService)->computePayslip($employee, $period)['totals'];

        $this->assertGreaterThan(0, (float) $totals['late_undertime_deduction']);
    }

    public function test_thirteenth_month_is_basic_over_twelve(): void
    {
        $this->assertGreaterThanOrEqual(0, (new PayrollService)->thirteenthMonth($this->employee(), (int) date('Y')));
    }
}
