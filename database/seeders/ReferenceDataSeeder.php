<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Production reference data only: statutory tables and lookup lists the app
 * needs to compute pay correctly. Safe to re-run (each seeder upserts).
 * Never run DatabaseSeeder on a live site — it plants demo employees.
 */
class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            GovernmentTableSeeder::class,
            LoanTypeSeeder::class,
            DocumentTypeSeeder::class,
            QaCriteriaSeeder::class,
            LeaveSeeder::class,
        ]);
    }
}
