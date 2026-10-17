<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\PagibigContribution;
use App\Models\PhilhealthContribution;
use App\Models\SssContribution;
use App\Models\TaxTable;
use App\Models\User;
use App\Services\PhContributionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GovernmentTableTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::create([
            'name' => ucfirst($role), 'email' => $role.'@hris.test', 'password' => 'password',
            'role' => $role, 'is_active' => true,
        ]);
    }

    public function test_payroll_user_can_view_government_tables(): void
    {
        $this->actingAs($this->user('payroll'))
            ->get('/government')
            ->assertOk()
            ->assertSee('SSS Contribution');
    }

    public function test_hr_user_cannot_manage_government_tables(): void
    {
        $hr = $this->user('hr');

        $this->actingAs($hr)->get('/government')->assertForbidden();
        $this->actingAs($hr)->put('/government/sss', [
            'min_msc' => 5000, 'max_msc' => 35000, 'step' => 500,
            'employee_rate' => 5, 'employer_rate' => 10, 'ec_share' => 10,
        ])->assertForbidden();
    }

    public function test_sss_schedule_rebuild_regenerates_brackets(): void
    {
        $this->actingAs($this->user('payroll'))->put('/government/sss', [
            'min_msc' => 5000, 'max_msc' => 35000, 'step' => 500,
            'employee_rate' => 5, 'employer_rate' => 10, 'ec_share' => 10,
        ])->assertRedirect();

        $this->assertSame(61, SssContribution::count());
        $this->assertSame(1, AuditLog::where('description', 'like', 'Rebuilt SSS schedule%')->count());

        $sss = (new PhContributionService)->sssMonthly(20000);
        $this->assertSame(1000.0, $sss['employee']);
        $this->assertSame(2000.0, $sss['employer']);
        $this->assertSame(10.0, $sss['ec']);
    }

    public function test_sss_schedule_rebuild_is_idempotent(): void
    {
        $user = $this->user('payroll');
        $payload = [
            'min_msc' => 5000, 'max_msc' => 35000, 'step' => 500,
            'employee_rate' => 5, 'employer_rate' => 10, 'ec_share' => 10,
        ];

        $this->actingAs($user)->put('/government/sss', $payload);
        $this->actingAs($user)->put('/government/sss', $payload);

        $this->assertSame(61, SssContribution::count());
    }

    public function test_sss_rebuild_rejects_invalid_range(): void
    {
        $this->actingAs($this->user('payroll'))->put('/government/sss', [
            'min_msc' => 35000, 'max_msc' => 5000, 'step' => 500,
            'employee_rate' => 5, 'employer_rate' => 10, 'ec_share' => 10,
        ])->assertSessionHasErrors('max_msc');
    }

    public function test_philhealth_year_can_be_added_and_updated(): void
    {
        $user = $this->user('payroll');

        $this->actingAs($user)->post('/government/philhealth', [
            'year' => 2026, 'min_salary' => 10000, 'max_salary' => 100000,
            'employee_rate' => 2.5, 'employer_rate' => 2.5,
        ])->assertRedirect();

        $row = PhilhealthContribution::where('year', 2026)->firstOrFail();
        $this->assertSame(0.025, (float) $row->employee_rate);
        $this->assertSame(500.0, (new PhContributionService)->philhealthMonthly(20000, 2026)['employee']);

        $this->actingAs($user)->put('/government/philhealth/'.$row->id, [
            'year' => 2026, 'min_salary' => 10000, 'max_salary' => 100000,
            'employee_rate' => 5, 'employer_rate' => 5,
        ])->assertRedirect();

        $this->assertSame(0.05, (float) $row->fresh()->employee_rate);
        $this->assertSame(1000.0, (new PhContributionService)->philhealthMonthly(20000, 2026)['employee']);
        $this->assertSame(1, AuditLog::where('action', 'update')->count());
    }

    public function test_philhealth_rejects_ceiling_below_floor(): void
    {
        $this->actingAs($this->user('payroll'))->post('/government/philhealth', [
            'year' => 2026, 'min_salary' => 100000, 'max_salary' => 10000,
            'employee_rate' => 2.5, 'employer_rate' => 2.5,
        ])->assertSessionHasErrors('max_salary');
    }

    public function test_philhealth_table_can_be_deleted(): void
    {
        $row = PhilhealthContribution::create([
            'year' => 2025, 'min_salary' => 10000, 'max_salary' => 100000,
            'employee_rate' => 0.025, 'employer_rate' => 0.025,
        ]);

        $this->actingAs($this->user('payroll'))
            ->delete('/government/philhealth/'.$row->id)
            ->assertRedirect();

        $this->assertSame(0, PhilhealthContribution::count());
    }

    public function test_pagibig_bracket_can_be_added_with_no_ceiling(): void
    {
        $this->actingAs($this->user('payroll'))->post('/government/pagibig', [
            'min_salary' => 0, 'max_salary' => null, 'employee_rate' => 2, 'employer_rate' => 2,
            'employee_cap' => 200, 'employer_cap' => 200,
        ])->assertRedirect();

        $row = PagibigContribution::firstOrFail();
        $this->assertNull($row->max_salary);
        $this->assertSame(0.02, (float) $row->employee_rate);
        $this->assertSame(200.0, (new PhContributionService)->pagibigMonthly(20000)['employee']);
    }

    public function test_tax_bracket_change_affects_withholding_tax(): void
    {
        $user = $this->user('payroll');

        $this->actingAs($user)->post('/government/tax', [
            'year' => 2026, 'period' => 'semi_monthly', 'min_taxable' => 0,
            'max_taxable' => null, 'base_tax' => 0, 'rate' => 10, 'excess_over' => 0,
        ])->assertRedirect();

        $row = TaxTable::firstOrFail();
        $this->assertSame(0.1, (float) $row->rate);
        $this->assertSame(2000.0, (new PhContributionService)->withholdingTax(20000, 'semi_monthly', 2026));

        $this->actingAs($user)->put('/government/tax/'.$row->id, [
            'year' => 2026, 'period' => 'semi_monthly', 'min_taxable' => 0,
            'max_taxable' => null, 'base_tax' => 0, 'rate' => 20, 'excess_over' => 0,
        ])->assertRedirect();

        $this->assertSame(4000.0, (new PhContributionService)->withholdingTax(20000, 'semi_monthly', 2026));
    }

    public function test_tax_bracket_can_be_deleted(): void
    {
        $row = TaxTable::create([
            'year' => 2026, 'period' => 'monthly', 'min_taxable' => 0,
            'max_taxable' => null, 'base_tax' => 0, 'rate' => 0.15, 'excess_over' => 0,
        ]);

        $this->actingAs($this->user('payroll'))
            ->delete('/government/tax/'.$row->id)
            ->assertRedirect();

        $this->assertSame(0, TaxTable::count());
    }
}
