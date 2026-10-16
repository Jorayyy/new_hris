<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Holiday;
use App\Models\LoanType;
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
            ['name' => 'IT Support', 'code' => 'IT', 'branch_id' => $ormoc->id],
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
            ['name' => 'Day Shift', 'time_in' => '08:00', 'time_out' => '17:00', 'is_night_diff' => false],
            ['name' => 'Mid Shift', 'time_in' => '14:00', 'time_out' => '23:00', 'is_night_diff' => true],
            ['name' => 'Night Shift', 'time_in' => '22:00', 'time_out' => '07:00', 'is_night_diff' => true],
        ] as $shift) {
            Shift::create($shift + ['break_minutes' => 60, 'grace_minutes' => 10, 'required_hours' => 8, 'is_active' => true]);
        }

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

        foreach ([
            ['name' => 'SSS Salary Loan', 'code' => 'SSS', 'interest_rate' => 0.10, 'max_amount' => 60000],
            ['name' => 'Pag-IBIG MPL', 'code' => 'PAGIBIG', 'interest_rate' => 0.1044, 'max_amount' => 80000],
            ['name' => 'Company Loan', 'code' => 'COMPANY', 'interest_rate' => 0, 'max_amount' => 50000],
            ['name' => 'Cash Advance', 'code' => 'CA', 'interest_rate' => 0, 'max_amount' => 10000],
        ] as $loanType) {
            LoanType::create($loanType + ['is_active' => true]);
        }

        Setting::put('company_name', 'Prime BPO Services Inc.');
        Setting::put('company_address', 'Real St., Tacloban City, Leyte');
        Setting::put('working_days_per_month', '22');
    }
}
