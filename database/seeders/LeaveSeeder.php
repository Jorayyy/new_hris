<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use Illuminate\Database\Seeder;

class LeaveSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Vacation Leave', 'code' => 'VL', 'is_paid' => true, 'pay_percentage' => 100, 'max_days_per_year' => 15, 'is_cash_convertible' => true, 'requires_attachment' => false],
            ['name' => 'Sick Leave', 'code' => 'SL', 'is_paid' => true, 'pay_percentage' => 100, 'max_days_per_year' => 15, 'is_cash_convertible' => false, 'requires_attachment' => true],
            ['name' => 'Maternity Leave', 'code' => 'ML', 'is_paid' => true, 'pay_percentage' => 100, 'max_days_per_year' => 105, 'is_cash_convertible' => false, 'requires_attachment' => true],
            ['name' => 'Paternity Leave', 'code' => 'PL', 'is_paid' => true, 'pay_percentage' => 100, 'max_days_per_year' => 7, 'is_cash_convertible' => false, 'requires_attachment' => true],
            ['name' => 'Service Incentive Leave', 'code' => 'SIL', 'is_paid' => true, 'pay_percentage' => 100, 'max_days_per_year' => 5, 'is_cash_convertible' => true, 'requires_attachment' => false],
            ['name' => 'Unpaid Leave', 'code' => 'UL', 'is_paid' => false, 'pay_percentage' => 0, 'max_days_per_year' => 0, 'is_cash_convertible' => false, 'requires_attachment' => false],
        ];
        foreach ($types as $type) {
            LeaveType::updateOrCreate(['name' => $type['name']], $type + ['is_active' => true]);
        }
    }
}
