<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GovernmentTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('sss_contributions')->truncate();
        $sss = [];
        for ($msc = 5000; $msc <= 35000; $msc += 500) {
            $sss[] = [
                'min_msc' => $msc,
                'max_msc' => $msc === 35000 ? 999999 : $msc + 499,
                'employee_share' => round($msc * 0.05, 2),
                'employer_share' => round($msc * 0.10, 2),
                'ec_share' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        DB::table('sss_contributions')->insert($sss);

        DB::table('philhealth_contributions')->truncate();
        DB::table('philhealth_contributions')->insert([
            'year' => (int) date('Y'),
            'min_salary' => 10000,
            'max_salary' => 100000,
            'employee_rate' => 0.025,
            'employer_rate' => 0.025,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('pagibig_contributions')->truncate();
        DB::table('pagibig_contributions')->insert([
            ['min_salary' => 0, 'max_salary' => 1500, 'employee_rate' => 0.01, 'employer_rate' => 0.02, 'employee_cap' => 200, 'employer_cap' => 200, 'created_at' => now(), 'updated_at' => now()],
            ['min_salary' => 1500.01, 'max_salary' => null, 'employee_rate' => 0.02, 'employer_rate' => 0.02, 'employee_cap' => 200, 'employer_cap' => 200, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('tax_tables')->truncate();
        $year = (int) date('Y');
        $semi = [
            [0, 10416.99, 0, 0, 0],
            [10417, 16666.99, 0, 0.15, 10417],
            [16667, 33332.99, 937.50, 0.20, 16667],
            [33333, 83332.99, 4270.70, 0.25, 33333],
            [83333, 333332.99, 16770.70, 0.30, 83333],
            [333333, null, 91770.70, 0.35, 333333],
        ];
        $monthly = [
            [0, 20832.99, 0, 0, 0],
            [20833, 33332.99, 0, 0.15, 20833],
            [33333, 66666.99, 1875.00, 0.20, 33333],
            [66667, 166666.99, 8541.80, 0.25, 66667],
            [166667, 666666.99, 33541.80, 0.30, 166667],
            [666667, null, 183541.80, 0.35, 666667],
        ];
        $rows = [];
        foreach (['semi_monthly' => $semi, 'monthly' => $monthly] as $period => $brackets) {
            foreach ($brackets as [$min, $max, $base, $rate, $excess]) {
                $rows[] = [
                    'year' => $year, 'period' => $period,
                    'min_taxable' => $min, 'max_taxable' => $max,
                    'base_tax' => $base, 'rate' => $rate, 'excess_over' => $excess,
                    'created_at' => now(), 'updated_at' => now(),
                ];
            }
        }
        DB::table('tax_tables')->insert($rows);
    }
}
