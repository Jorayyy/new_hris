<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeAllowance;
use App\Models\EmployeeShift;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\Position;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $branches = Branch::all();
        $departments = Department::all();
        $positions = Position::all();
        $shifts = Shift::all();
        $leaveTypes = LeaveType::whereIn('code', ['VL', 'SL', 'SIL'])->get();

        $people = [
            ['Juan', 'Santos', 'Dela Cruz', 'CSR', 'regular', 20000],
            ['Ana', 'Lopez', 'Reyes', 'TL', 'regular', 32000],
            ['Mark', 'Bautista', 'Villanueva', 'CSR', 'probationary', 18000],
            ['Grace', 'Tan', 'Uy', 'RC', 'regular', 23000],
            ['Kevin', 'Lim', 'Go', 'CSR', 'regular', 19500],
            ['Rhea', 'Cruz', 'Mendoza', 'HRS', 'regular', 27000],
            ['Daniel', 'Ong', 'Sy', 'ITS', 'regular', 25000],
            ['Liza', 'Ramos', 'Fernandez', 'CSR', 'probationary', 18000],
            ['Paul', 'Garcia', 'Navarro', 'CSR', 'regular', 21000],
            ['Donna', 'Flores', 'Aguilar', 'OM', 'regular', 55000],
            ['Ryan', 'Aquino', 'Torres', 'CSR', 'regular', 20000],
            ['Ella', 'Pascual', 'Rivera', 'CSR', 'regular', 20500],
        ];

        $employeeUser = null;

        foreach ($people as $index => [$first, $middle, $last, $code, $status, $salary]) {
            $position = $positions->firstWhere('code', $code);
            $branch = $branches[$index % $branches->count()];
            $department = $departments->firstWhere('id', $position?->department_id) ?? $departments->first();

            $employee = Employee::create([
                'employee_no' => 'EMP'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                'pin' => '1234',
                'branch_id' => $branch->id,
                'department_id' => $department->id,
                'position_id' => $position?->id,
                'first_name' => $first,
                'middle_name' => $middle,
                'last_name' => $last,
                'birth_date' => now()->subYears(25 + $index)->startOfYear(),
                'gender' => $index % 2 === 0 ? 'Male' : 'Female',
                'civil_status' => $index % 3 === 0 ? 'Married' : 'Single',
                'address' => 'Tacloban City, Leyte',
                'contact_no' => '0917'.str_pad((string) (1000000 + $index), 7, '0', STR_PAD_LEFT),
                'personal_email' => strtolower($first.'.'.$last).'@email.test',
                'date_hired' => now()->subMonths(6 + $index)->startOfMonth(),
                'employment_status' => $status,
                'sss_no' => '34-'.str_pad((string) (1000000 + $index), 7, '0', STR_PAD_LEFT).'-1',
                'philhealth_no' => '12-'.str_pad((string) (2000000 + $index), 7, '0', STR_PAD_LEFT).'-3',
                'pagibig_no' => '1210-'.str_pad((string) (3000000 + $index), 7, '0', STR_PAD_LEFT).'-1',
                'tin' => str_pad((string) (4000000 + $index), 9, '0', STR_PAD_LEFT).'000',
                'bank_name' => 'BDO',
                'bank_account_no' => '00'.str_pad((string) (500000000 + $index), 10, '0', STR_PAD_LEFT),
                'basic_salary' => $salary,
                'monthly_allowance' => $index % 2 === 0 ? 2000 : 0,
                'tax_status' => 'S',
                'is_active' => true,
            ]);

            if ($shifts->isNotEmpty()) {
                EmployeeShift::create([
                    'employee_id' => $employee->id,
                    'shift_id' => $shifts[$index % $shifts->count()]->id,
                    'effective_from' => now()->startOfYear()->toDateString(),
                    'published_at' => now(),
                ]);
            }

            if ($index % 2 === 0) {
                EmployeeAllowance::create([
                    'employee_id' => $employee->id,
                    'name' => 'Rice Allowance',
                    'amount' => 2000,
                    'is_taxable' => false,
                    'is_recurring' => true,
                ]);
            }

            foreach ($leaveTypes as $type) {
                LeaveBalance::create([
                    'employee_id' => $employee->id,
                    'leave_type_id' => $type->id,
                    'year' => now()->year,
                    'credits' => $type->max_days_per_year,
                    'used' => 0,
                ]);
            }

            if ($index === 0) {
                $employeeUser = $employee;
            }
        }

        if ($employeeUser) {
            $user = User::updateOrCreate(['email' => 'employee@hris.test'], [
                'name' => $employeeUser->full_name,
                'password' => 'password',
                'role' => User::ROLE_EMPLOYEE,
                'branch_id' => $employeeUser->branch_id,
                'is_active' => true,
            ]);
            $employeeUser->update(['user_id' => $user->id]);
        }
    }
}
