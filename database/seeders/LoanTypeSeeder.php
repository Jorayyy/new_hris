<?php

namespace Database\Seeders;

use App\Models\LoanType;
use Illuminate\Database\Seeder;

class LoanTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'SSS Salary Loan', 'interest_rate' => 0.10, 'is_government' => true],
            ['name' => 'Pag-IBIG Multi-Purpose Loan', 'interest_rate' => 0.1069, 'is_government' => true],
            ['name' => 'Company Loan', 'interest_rate' => 0, 'is_government' => false],
            ['name' => 'Cash Advance', 'interest_rate' => 0, 'is_government' => false],
        ];

        foreach ($types as $type) {
            LoanType::updateOrCreate(['name' => $type['name']], $type + ['is_active' => true]);
        }
    }
}
