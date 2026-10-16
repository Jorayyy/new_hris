<?php

namespace Tests\Feature;

use App\Services\PhContributionService;
use Database\Seeders\GovernmentTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhContributionTest extends TestCase
{
    use RefreshDatabase;

    private PhContributionService $ph;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(GovernmentTableSeeder::class);
        $this->ph = new PhContributionService;
    }

    public function test_sss_employee_share(): void
    {
        $this->assertSame(1000.0, $this->ph->sssMonthly(20000)['employee']);
    }

    public function test_philhealth_employee_share(): void
    {
        $this->assertSame(500.0, $this->ph->philhealthMonthly(20000)['employee']);
    }

    public function test_philhealth_respects_floor_and_ceiling(): void
    {
        $this->assertSame(250.0, $this->ph->philhealthMonthly(5000)['employee']);
        $this->assertSame(2500.0, $this->ph->philhealthMonthly(500000)['employee']);
    }

    public function test_pagibig_capped_at_200(): void
    {
        $this->assertSame(200.0, $this->ph->pagibigMonthly(20000)['employee']);
        $this->assertSame(100.0, $this->ph->pagibigMonthly(5000)['employee']);
    }

    public function test_bir_tax_semi_monthly_bracket(): void
    {
        $this->assertSame(0.0, $this->ph->withholdingTax(10000, 'semi_monthly'));
        $this->assertSame(1604.10, $this->ph->withholdingTax(20000, 'semi_monthly'));
    }

    public function test_bir_tax_zero_below_threshold(): void
    {
        $this->assertSame(0.0, $this->ph->withholdingTax(0, 'semi_monthly'));
        $this->assertSame(0.0, $this->ph->withholdingTax(5000, 'monthly'));
    }
}
