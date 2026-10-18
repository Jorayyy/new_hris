<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Holiday;
use App\Models\Position;
use App\Models\Setting;
use App\Models\Shift;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $hq = Branch::create(['name' => 'Tacloban HQ', 'code' => 'TAC', 'address' => 'Real St., Tacloban City', 'is_active' => true]);
        $ormoc = Branch::create(['name' => 'Ormoc Site', 'code' => 'ORM', 'address' => 'Ormoc City, Leyte', 'is_active' => true]);

        $departments = [
            ['name' => 'Operations', 'code' => 'OPS', 'branch_id' => $hq->id],
            ['name' => 'Recruitment', 'code' => 'REC', 'branch_id' => $hq->id],
            ['name' => 'Human Resources', 'code' => 'HR', 'branch_id' => $hq->id],
            ['name' => 'Finance', 'code' => 'FIN', 'branch_id' => $hq->id],
            ['name' => 'IT Support', 'code' => 'IT', 'branch_id' => $hq->id],
        ];
        foreach ($departments as $department) {
            Department::create($department + ['is_active' => true]);
        }

        $positions = [
            ['title' => 'Customer Service Representative', 'code' => 'CSR', 'default_salary' => 18000],
            ['title' => 'Team Lead', 'code' => 'TL', 'default_salary' => 28000],
            ['title' => 'Recruiter', 'code' => 'RC', 'default_salary' => 22000],
            ['title' => 'HR Specialist', 'code' => 'HRS', 'default_salary' => 25000],
            ['title' => 'Payroll Officer', 'code' => 'PO', 'default_salary' => 26000],
            ['title' => 'IT Support', 'code' => 'ITS', 'default_salary' => 24000],
            ['title' => 'Operations Manager', 'code' => 'OM', 'default_salary' => 55000],
        ];
        foreach ($positions as $position) {
            Position::create($position + ['is_active' => true]);
        }

        foreach ([
            ['name' => 'Graveyard 8PM-6AM', 'time_in' => '20:00', 'time_out' => '06:00', 'is_night_diff' => true],
            ['name' => 'Graveyard 9PM-6AM', 'time_in' => '21:00', 'time_out' => '06:00', 'is_night_diff' => true],
        ] as $shift) {
            Shift::create($shift + ['break_minutes' => 60, 'grace_minutes' => 10, 'required_hours' => 8, 'is_active' => true]);
        }

        // Ormoc Site stays as a branch for dropdowns but has no staff (Tacloban-only demo).

        foreach ([
            ['date' => now()->year.'-01-01', 'name' => "New Year's Day", 'type' => 'regular', 'rate_multiplier' => 2],
            ['date' => now()->year.'-04-09', 'name' => 'Araw ng Kagitingan', 'type' => 'regular', 'rate_multiplier' => 2],
            ['date' => now()->year.'-05-01', 'name' => 'Labor Day', 'type' => 'regular', 'rate_multiplier' => 2],
            ['date' => now()->year.'-06-12', 'name' => 'Independence Day', 'type' => 'regular', 'rate_multiplier' => 2],
            ['date' => now()->year.'-11-30', 'name' => 'Bonifacio Day', 'type' => 'regular', 'rate_multiplier' => 2],
            ['date' => now()->year.'-12-25', 'name' => 'Christmas Day', 'type' => 'regular', 'rate_multiplier' => 2],
            ['date' => now()->year.'-08-21', 'name' => 'Ninoy Aquino Day', 'type' => 'special', 'rate_multiplier' => 1.3],
        ] as $holiday) {
            Holiday::create($holiday + ['branch_id' => null]);
        }

        Setting::put('company_name', 'Prime BPO Services Inc.');
        Setting::put('company_address', 'Real St., Tacloban City, Leyte');
        Setting::put('working_days_per_month', '22');
    }
}
