<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class EmployeeImportTest extends TestCase
{
    use RefreshDatabase;

    private function hr(): User
    {
        return User::create([
            'name' => 'HR', 'email' => 'hr@test.local',
            'password' => 'password', 'role' => User::ROLE_HR, 'is_active' => true,
        ]);
    }

    private function csv(array $rows): UploadedFile
    {
        $header = 'employee_no,first_name,last_name,branch,department,position,employment_status,date_hired,basic_salary';

        return UploadedFile::fake()->createWithContent('employees.csv', implode("\n", [$header, ...$rows]));
    }

    public function test_it_imports_employees_and_matches_org_names(): void
    {
        $branch = Branch::create(['name' => 'Tacloban HQ', 'code' => 'TAC']);
        $department = Department::create(['name' => 'Operations', 'code' => 'OPS', 'branch_id' => $branch->id]);
        $position = Position::create(['title' => 'Customer Service Representative', 'code' => 'CSR']);

        $this->actingAs($this->hr())->post('/employees/import', [
            'file' => $this->csv(['EMP0001,Juan,Cruz,Tacloban HQ,Operations,Customer Service Representative,regular,2026-01-15,18000']),
        ])->assertRedirect(route('employees.import.form'));

        $employee = Employee::where('employee_no', 'EMP0001')->firstOrFail();

        $this->assertSame('Juan', $employee->first_name);
        $this->assertSame($branch->id, $employee->branch_id);
        $this->assertSame($department->id, $employee->department_id);
        $this->assertSame($position->id, $employee->position_id);
        $this->assertSame('regular', $employee->employment_status);
        $this->assertSame('18000.00', $employee->basic_salary);
    }

    public function test_it_skips_invalid_rows(): void
    {
        $this->actingAs($this->hr())->post('/employees/import', [
            'file' => $this->csv([
                'EMP0001,Juan,Cruz',
                'EMP0002,NoLastName,',
                ',MissingNumber,Person',
            ]),
        ])->assertSessionHas('import_result', fn ($result) => $result['created'] === 1 && $result['skipped'] === 2);

        $this->assertDatabaseCount('employees', 1);
        $this->assertDatabaseHas('employees', ['employee_no' => 'EMP0001']);
    }

    public function test_preview_validates_without_saving(): void
    {
        $this->actingAs($this->hr())->post('/employees/import', [
            'file' => $this->csv(['EMP0001,Juan,Cruz']),
            'preview' => '1',
        ])->assertSessionHas('import_result', fn ($result) => $result['created'] === 1 && $result['preview'] === true);

        $this->assertDatabaseCount('employees', 0);
    }

    public function test_re_importing_the_same_employee_number_updates_it(): void
    {
        $hr = $this->hr();
        $file = fn (string $salary) => $this->csv(['EMP0001,Juan,Cruz,,,,regular,2026-01-15,'.$salary]);

        $this->actingAs($hr)->post('/employees/import', ['file' => $file('18000')]);
        $this->actingAs($hr)->post('/employees/import', ['file' => $file('20000')])
            ->assertSessionHas('import_result', fn ($result) => $result['created'] === 0 && $result['updated'] === 1);

        $this->assertDatabaseCount('employees', 1);
        $this->assertSame('20000.00', Employee::where('employee_no', 'EMP0001')->first()->basic_salary);
    }

    public function test_the_export_can_be_re_imported(): void
    {
        $branch = Branch::create(['name' => 'Tacloban HQ', 'code' => 'TAC']);
        Employee::create([
            'employee_no' => 'EMP0001', 'first_name' => 'Juan', 'last_name' => 'Cruz',
            'branch_id' => $branch->id, 'employment_status' => 'regular', 'basic_salary' => 18000,
        ]);

        $hr = $this->hr();
        $csv = $this->actingAs($hr)->get('/employees/export')->assertOk()->getContent();

        Employee::query()->delete();

        $this->actingAs($hr)->post('/employees/import', [
            'file' => UploadedFile::fake()->createWithContent('employees.csv', $csv),
        ])->assertSessionHas('import_result', fn ($result) => $result['created'] === 1 && $result['skipped'] === 0);

        $this->assertDatabaseHas('employees', [
            'employee_no' => 'EMP0001', 'first_name' => 'Juan', 'last_name' => 'Cruz', 'branch_id' => $branch->id,
        ]);
    }
}
