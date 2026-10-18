<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->warn('Production: seeding reference data only. Demo users, employees and payslips were skipped.');

            $this->call(ReferenceDataSeeder::class);

            return;
        }

        $this->call([
            ReferenceDataSeeder::class,
            OrganizationSeeder::class,
            UserSeeder::class,
            AnnouncementSeeder::class,
            EmployeeSeeder::class,
            AttendanceSeeder::class,
            PayrollSeeder::class,
        ]);
    }
}
