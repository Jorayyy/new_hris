<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            GovernmentTableSeeder::class,
            OrganizationSeeder::class,
            LoanTypeSeeder::class,
            DocumentTypeSeeder::class,
            LeaveSeeder::class,
            UserSeeder::class,
            EmployeeSeeder::class,
            AttendanceSeeder::class,
            PayrollSeeder::class,
        ]);
    }
}
