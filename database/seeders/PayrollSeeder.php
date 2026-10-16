<?php

namespace Database\Seeders;

use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Services\PayrollService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PayrollSeeder extends Seeder
{
    public function run(): void
    {
        $prev = Carbon::now()->subMonthNoOverflow();

        $periods = [
            [
                'name' => $prev->format('M').' 1-15',
                'date_from' => $prev->copy()->startOfMonth()->toDateString(),
                'date_to' => $prev->copy()->day(15)->toDateString(),
                'pay_date' => $prev->copy()->day(20)->toDateString(),
            ],
            [
                'name' => $prev->format('M').' 16-30',
                'date_from' => $prev->copy()->day(16)->toDateString(),
                'date_to' => $prev->copy()->endOfMonth()->toDateString(),
                'pay_date' => $prev->copy()->endOfMonth()->addDays(5)->toDateString(),
            ],
            [
                'name' => now()->format('M').' 1-15',
                'date_from' => now()->copy()->startOfMonth()->toDateString(),
                'date_to' => now()->copy()->day(15)->toDateString(),
                'pay_date' => now()->copy()->day(20)->toDateString(),
            ],
        ];

        $created = [];
        foreach ($periods as $period) {
            $created[] = PayrollPeriod::create($period + ['frequency' => 'semi_monthly', 'status' => 'open', 'branch_id' => null]);
        }

        $run = PayrollRun::create([
            'payroll_period_id' => $created[0]->id,
            'branch_id' => null,
            'status' => 'draft',
        ]);

        (new PayrollService)->processRun($run);
    }
}
