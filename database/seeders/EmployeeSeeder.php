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
        $branch = Branch::where('code', 'TAC')->firstOrFail();
        $positions = Position::all()->keyBy('code');
        $departments = Department::all()->keyBy('code');
        $shifts = Shift::orderBy('id')->get();
        $leaveTypes = LeaveType::whereIn('code', ['VL', 'SL', 'SIL'])->get();

        $this->command?->info('EmployeeSeeder: creating 50 Tacloban employees...');

        // 30 CSR, 6 TL, 4 RC, 3 HRS, 3 ITS, 2 PO, 2 OM = 50
        $codes = array_merge(
            array_fill(0, 30, 'CSR'),
            array_fill(0, 6, 'TL'),
            array_fill(0, 4, 'RC'),
            array_fill(0, 3, 'HRS'),
            array_fill(0, 3, 'ITS'),
            array_fill(0, 2, 'PO'),
            array_fill(0, 2, 'OM'),
        );

        $firstNames = [
            'Juan', 'Maria', 'Jose', 'Ana', 'Mark', 'Grace', 'Kevin', 'Rhea', 'Daniel', 'Liza',
            'Paul', 'Donna', 'Ryan', 'Ella', 'Carlo', 'Joy', 'Miguel', 'Angel', 'Rafael', 'Nicole',
            'Joshua', 'Kate', 'Adrian', 'Bea', 'Christian', 'Diane', 'Eric', 'Faith', 'Gabriel', 'Hannah',
            'Ivan', 'Jasmine', 'Lester', 'Karen', 'Marco', 'Lorraine', 'Nathan', 'Michelle', 'Oliver', 'Patricia',
            'Patrick', 'Queenie', 'Rico', 'Samantha', 'Samuel', 'Trisha', 'Victor', 'Wendy', 'Xavier', 'Yvonne',
        ];

        $lastNames = [
            'Dela Cruz', 'Reyes', 'Santos', 'Villanueva', 'Uy', 'Go', 'Mendoza', 'Sy', 'Fernandez', 'Navarro',
            'Aguilar', 'Torres', 'Rivera', 'Lopez', 'Garcia', 'Bautista', 'Tan', 'Lim', 'Cruz', 'Ong',
            'Ramos', 'Flores', 'Aquino', 'Pascual', 'Castillo', 'Domingo', 'Salazar', 'Mercado', 'Rosales', 'Gonzales',
            'Padilla', 'Vega', 'Santiago', 'Molina', 'Cortez', 'Rojas', 'Del Rosario', 'Buenaventura', 'Marquez', 'Valdez',
            'Alonzo', 'Ferrer', 'Ibarra', 'Lorenzo', 'Manalo', 'Ocampo', 'Panganiban', 'Quinto', 'Robles', 'Serrano',
        ];

        $middleNames = [
            'Lim', 'Cruz', 'Tan', 'Reyes', 'Santos', 'Garcia', 'Uy', 'Ong', 'Sy', 'Lopez',
            'Mendoza', 'Torres', 'Rivera', 'Navarro', 'Bautista', 'Ramos', 'Flores', 'Aquino', 'Pascual', 'Castillo',
        ];

        $deptByCode = [
            'CSR' => 'OPS', 'TL' => 'OPS', 'OM' => 'OPS',
            'RC' => 'REC', 'HRS' => 'HR', 'PO' => 'FIN', 'ITS' => 'IT',
        ];

        $employeeUser = null;

        foreach ($codes as $index => $code) {
            $position = $positions->get($code);
            $department = $departments->get($deptByCode[$code]) ?? $departments->first();
            $shift = $shifts->isNotEmpty() ? $shifts[$index % $shifts->count()] : null;
            $slug = strtolower($firstNames[$index].'.'.str_replace(' ', '', $lastNames[$index]));

            $employee = Employee::create([
                'employee_no' => 'EMP'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                'pin' => '1234',
                'branch_id' => $branch->id,
                'department_id' => $department->id,
                'position_id' => $position?->id,
                'first_name' => $firstNames[$index],
                'middle_name' => $middleNames[$index % count($middleNames)],
                'last_name' => $lastNames[$index],
                'birth_date' => now()->subYears(24 + ($index % 20))->startOfYear(),
                'gender' => $index % 2 === 0 ? 'Male' : 'Female',
                'civil_status' => $index % 3 === 0 ? 'Married' : 'Single',
                'address' => 'Tacloban City, Leyte',
                'contact_no' => '0917'.str_pad((string) (1000000 + $index), 7, '0', STR_PAD_LEFT),
                'personal_email' => $slug.'@email.test',
                'date_hired' => now()->subMonths(8 + ($index % 12))->startOfMonth(),
                'employment_status' => $index % 6 === 0 ? 'probationary' : 'regular',
                'rest_days' => [0, 6],
                'sss_no' => '34-'.str_pad((string) (1000000 + $index), 7, '0', STR_PAD_LEFT).'-1',
                'philhealth_no' => '12-'.str_pad((string) (2000000 + $index), 7, '0', STR_PAD_LEFT).'-3',
                'pagibig_no' => '1210-'.str_pad((string) (3000000 + $index), 7, '0', STR_PAD_LEFT).'-1',
                'tin' => str_pad((string) (4000000 + $index), 9, '0', STR_PAD_LEFT).'000',
                'bank_name' => 'BDO',
                'bank_account_no' => '00'.str_pad((string) (500000000 + $index), 10, '0', STR_PAD_LEFT),
                'basic_salary' => ($position?->default_salary ?? 18000) + ($index % 5) * 500,
                'monthly_allowance' => $index % 2 === 0 ? 2000 : 0,
                'tax_status' => 'S',
                'is_active' => true,
            ]);

            if ($shift) {
                EmployeeShift::create([
                    'employee_id' => $employee->id,
                    'shift_id' => $shift->id,
                    'effective_from' => now()->subMonths(9)->startOfMonth()->toDateString(),
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

        $this->command?->info('EmployeeSeeder: done.');
    }
}
