<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Services\PayrollService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Weekly payroll runs covering the same six months as the attendance seed,
 * so pay trends and payslips have data.
 */
class PayrollSeeder extends Seeder
{
    public function run(): void
    {
        $payroll = new PayrollService;
        $firstDay = Attendance::min('work_date');
        $start = $firstDay
            ? Carbon::parse($firstDay)->startOfWeek(Carbon::MONDAY)
            : Carbon::now()->subMonths(6)->startOfWeek(Carbon::MONDAY);
        $today = Carbon::now();

        $this->command?->info('PayrollSeeder: weekly runs from '.$start->toDateString().'...');

        $runs = 0;
        $cursor = $start->copy();

        while ($cursor->lessThanOrEqualTo($today)) {
            $from = $cursor->copy();
            $to = $cursor->copy()->endOfWeek(Carbon::SUNDAY);

            // Skip weeks with no attendance: statutory deductions alone would make net pay negative.
            if (! Attendance::workDateBetween($from, $to)->exists()) {
                $cursor->addWeek();

                continue;
            }

            $period = PayrollPeriod::create([
                'name' => 'Week of '.$from->format('M j, Y'),
                'date_from' => $from->toDateString(),
                'date_to' => $to->toDateString(),
                'pay_date' => $to->copy()->addDays(5)->toDateString(),
                'frequency' => 'weekly',
                'status' => 'open',
                'branch_id' => null,
            ]);

            $run = PayrollRun::create([
                'payroll_period_id' => $period->id,
                'branch_id' => null,
                'status' => PayrollRun::STATUS_DRAFT,
            ]);

            $payroll->processRun($run);
            $runs++;

            $cursor->addWeek();
        }

        $this->command?->info("PayrollSeeder: {$runs} weekly runs processed.");
    }
}
