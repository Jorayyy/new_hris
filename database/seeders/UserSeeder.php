<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $hq = Branch::where('code', 'TAC')->first();

        User::updateOrCreate(['email' => 'admin@hris.test'], [
            'name' => 'System Administrator', 'password' => 'password',
            'role' => User::ROLE_SUPER_ADMIN, 'branch_id' => $hq?->id, 'is_active' => true,
        ]);
        User::updateOrCreate(['email' => 'hr@hris.test'], [
            'name' => 'Hannah Reyes', 'password' => 'password',
            'role' => User::ROLE_HR, 'branch_id' => $hq?->id, 'is_active' => true,
        ]);
        User::updateOrCreate(['email' => 'payroll@hris.test'], [
            'name' => 'Paolo Dela Cruz', 'password' => 'password',
            'role' => User::ROLE_PAYROLL, 'branch_id' => $hq?->id, 'is_active' => true,
        ]);
        User::updateOrCreate(['email' => 'manager@hris.test'], [
            'name' => 'Maria Santos', 'password' => 'password',
            'role' => User::ROLE_MANAGER, 'branch_id' => $hq?->id, 'is_active' => true,
        ]);
    }
}
