<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Department;
use App\Models\DocumentType;
use App\Models\Employee;
use App\Models\Position;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $employees = Employee::query()
            ->with(['branch', 'department', 'position'])
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(fn ($w) => $w->where('first_name', 'like', $term)->orWhere('last_name', 'like', $term)->orWhere('employee_no', 'like', $term));
            })
            ->when($request->filled('branch'), fn ($q) => $q->where('branch_id', $request->integer('branch')))
            ->when($request->filled('status'), fn ($q) => $q->where('employment_status', $request->string('status')))
            ->orderBy('last_name')
            ->paginate(20)
            ->withQueryString();

        return view('employees.index', [
            'employees' => $employees,
            'branches' => Branch::orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('employees.form', $this->formData(new Employee(['employment_status' => 'probationary', 'tax_status' => 'S', 'is_active' => true])));
    }

    public function store(Request $request): RedirectResponse
    {
        $employee = Employee::create($this->validated($request));
        AuditLog::record('create', 'Created employee '.$employee->employee_no.' '.$employee->full_name, $employee);

        return redirect()->route('employees.show', $employee)->with('ok', 'Employee created.');
    }

    public function show(Employee $employee): View
    {
        $employee->load(['branch', 'department', 'position', 'documents.documentType', 'allowances', 'deductions']);

        return view('employees.show', [
            'employee' => $employee,
            'documentTypes' => DocumentType::where('is_active', true)->orderBy('sort')->orderBy('name')->get(),
            'payslips' => $employee->payslips()->with('run.period')->latest()->take(8)->get(),
            'attendances' => $employee->attendances()->latest('work_date')->take(15)->get(),
            'leaveBalances' => $employee->leaveBalances()->where('year', now()->year)->with('leaveType')->get(),
        ]);
    }

    public function edit(Employee $employee): View
    {
        return view('employees.form', $this->formData($employee));
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $employee->update($this->validated($request, $employee));
        AuditLog::record('update', 'Updated employee '.$employee->employee_no.' '.$employee->full_name, $employee);

        return redirect()->route('employees.show', $employee)->with('ok', 'Employee updated.');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        AuditLog::record('delete', 'Deleted employee '.$employee->employee_no.' '.$employee->full_name, $employee);
        $employee->delete();

        return redirect()->route('employees.index')->with('ok', 'Employee deleted.');
    }

    public function export(Request $request): Response
    {
        $employees = Employee::query()
            ->with(['branch', 'department', 'position'])
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(fn ($w) => $w->where('first_name', 'like', $term)->orWhere('last_name', 'like', $term)->orWhere('employee_no', 'like', $term));
            })
            ->when($request->filled('branch'), fn ($q) => $q->where('branch_id', $request->integer('branch')))
            ->when($request->filled('status'), fn ($q) => $q->where('employment_status', $request->string('status')))
            ->orderBy('last_name')
            ->get();

        $rows = [['employee_no', 'first_name', 'middle_name', 'last_name', 'suffix', 'branch', 'department', 'position', 'employment_status', 'date_hired', 'basic_salary', 'monthly_allowance', 'tax_status', 'contact_no', 'personal_email', 'sss_no', 'philhealth_no', 'pagibig_no', 'tin', 'bank_name', 'bank_account_no']];
        foreach ($employees as $employee) {
            $rows[] = [
                $employee->employee_no,
                $employee->first_name,
                $employee->middle_name,
                $employee->last_name,
                $employee->suffix,
                $employee->branch?->name,
                $employee->department?->name,
                $employee->position?->title,
                $employee->employment_status,
                optional($employee->date_hired)->toDateString(),
                number_format((float) $employee->basic_salary, 2, '.', ''),
                number_format((float) $employee->monthly_allowance, 2, '.', ''),
                $employee->tax_status,
                $employee->contact_no,
                $employee->personal_email,
                $employee->sss_no,
                $employee->philhealth_no,
                $employee->pagibig_no,
                $employee->tin,
                $employee->bank_name,
                $employee->bank_account_no,
            ];
        }

        return $this->csv($rows, 'employees.csv');
    }

    public function importForm(): View
    {
        return view('employees.import');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt']]);

        $branches = $this->nameMap(Branch::all(), 'name');
        $departments = $this->nameMap(Department::all(), 'name');
        $positions = $this->nameMap(Position::all(), 'title');

        $preview = $request->boolean('preview');
        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $header = null;
        $line = 1;
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        while (($row = fgetcsv($handle)) !== false) {
            $line++;

            if ($header === null) {
                $header = array_map(fn ($name) => (string) Str::of((string) $name)->trim()->lower()->replace([' ', '-'], '_'), $row);

                continue;
            }

            if (implode('', $row) === '') {
                continue;
            }

            $values = [];
            foreach ($header as $index => $key) {
                $values[$key] = trim((string) ($row[$index] ?? ''));
            }

            foreach (['employee_number' => 'employee_no', 'emp_no' => 'employee_no', 'branch_name' => 'branch', 'department_name' => 'department', 'job_title' => 'position', 'title' => 'position', 'email' => 'personal_email', 'mobile' => 'contact_no'] as $alias => $field) {
                if (! isset($values[$field]) && isset($values[$alias])) {
                    $values[$field] = $values[$alias];
                }
            }

            $payload = [
                'employee_no' => $values['employee_no'] ?? '',
                'first_name' => $values['first_name'] ?? '',
                'middle_name' => ($values['middle_name'] ?? '') ?: null,
                'last_name' => $values['last_name'] ?? '',
                'suffix' => ($values['suffix'] ?? '') ?: null,
                'branch_id' => $branches[mb_strtolower($values['branch'] ?? '')] ?? null,
                'department_id' => $departments[mb_strtolower($values['department'] ?? '')] ?? null,
                'position_id' => $positions[mb_strtolower($values['position'] ?? '')] ?? null,
                'employment_status' => ($values['employment_status'] ?? '') ?: 'probationary',
                'date_hired' => ($values['date_hired'] ?? '') ?: null,
                'basic_salary' => ($values['basic_salary'] ?? '') ?: 0,
                'monthly_allowance' => ($values['monthly_allowance'] ?? '') ?: 0,
                'tax_status' => ($values['tax_status'] ?? '') ?: 'S',
                'contact_no' => ($values['contact_no'] ?? '') ?: null,
                'personal_email' => ($values['personal_email'] ?? '') ?: null,
                'sss_no' => ($values['sss_no'] ?? '') ?: null,
                'philhealth_no' => ($values['philhealth_no'] ?? '') ?: null,
                'pagibig_no' => ($values['pagibig_no'] ?? '') ?: null,
                'tin' => ($values['tin'] ?? '') ?: null,
                'bank_name' => ($values['bank_name'] ?? '') ?: null,
                'bank_account_no' => ($values['bank_account_no'] ?? '') ?: null,
                'is_active' => true,
            ];

            $validator = Validator::make($payload, [
                'employee_no' => ['required', 'string', 'max:50'],
                'first_name' => ['required', 'string', 'max:100'],
                'last_name' => ['required', 'string', 'max:100'],
                'employment_status' => ['required', 'string', 'max:30'],
                'date_hired' => ['nullable', 'date'],
                'basic_salary' => ['nullable', 'numeric', 'min:0'],
                'monthly_allowance' => ['nullable', 'numeric', 'min:0'],
                'personal_email' => ['nullable', 'email', 'max:255'],
            ]);

            if ($validator->fails()) {
                $skipped++;
                $errors[] = 'Row '.$line.': '.implode(' ', $validator->errors()->all());

                continue;
            }

            $existing = Employee::where('employee_no', $payload['employee_no'])->first();

            if ($existing) {
                if (! $preview) {
                    $existing->update($payload);
                }
                $updated++;
            } else {
                if (! $preview) {
                    Employee::create($payload);
                }
                $created++;
            }
        }
        fclose($handle);

        if (! $preview) {
            AuditLog::record('create', "Imported employees from CSV ({$created} created, {$updated} updated, {$skipped} skipped)");
        }

        return redirect()->route('employees.import.form')->with('import_result', [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => array_slice($errors, 0, 25),
            'preview' => $preview,
        ]);
    }

    private function nameMap($records, string $column): array
    {
        return $records->mapWithKeys(fn ($record) => [mb_strtolower(trim((string) $record->{$column})) => $record->id])->all();
    }

    private function csv(array $rows, string $filename): Response
    {
        $csv = '';
        foreach ($rows as $row) {
            $csv .= implode(',', array_map(fn ($value) => '"'.str_replace('"', '""', (string) $value).'"', $row))."\r\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function formData(Employee $employee): array
    {
        return [
            'employee' => $employee,
            'branches' => Branch::orderBy('name')->get(),
            'departments' => Department::orderBy('name')->get(),
            'positions' => Position::orderBy('title')->get(),
        ];
    }

    private function validated(Request $request, ?Employee $employee = null): array
    {
        $data = $request->validate([
            'employee_no' => ['required', 'string', 'max:50', 'unique:employees,employee_no'.($employee ? ','.$employee->id : '')],
            'pin' => ['nullable', 'digits:4'],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'suffix' => ['nullable', 'string', 'max:20'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'position_id' => ['nullable', 'exists:positions,id'],
            'birth_date' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'max:20'],
            'civil_status' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'contact_no' => ['nullable', 'string', 'max:30'],
            'personal_email' => ['nullable', 'email', 'max:255'],
            'date_hired' => ['nullable', 'date'],
            'date_regularized' => ['nullable', 'date'],
            'employment_status' => ['required', 'string', 'max:30'],
            'sss_no' => ['nullable', 'string', 'max:30'],
            'philhealth_no' => ['nullable', 'string', 'max:30'],
            'pagibig_no' => ['nullable', 'string', 'max:30'],
            'tin' => ['nullable', 'string', 'max:30'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_no' => ['nullable', 'string', 'max:50'],
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'monthly_allowance' => ['nullable', 'numeric', 'min:0'],
            'daily_rate' => ['nullable', 'numeric', 'min:0'],
            'tax_status' => ['required', 'string', 'max:10'],
            'dependents_count' => ['nullable', 'integer', 'min:0', 'max:20'],
            'rest_days' => ['nullable', 'array'],
            'rest_days.*' => ['integer', 'between:0,6'],
            'is_active' => ['boolean'],
        ]);

        if (empty($data['pin'])) {
            unset($data['pin']);
        }

        $data['rest_days'] = array_values(array_unique(array_map('intval', (array) $request->input('rest_days', []))));

        return $data + [
            'is_active' => $request->boolean('is_active'),
            'monthly_allowance' => $request->input('monthly_allowance', 0),
            'daily_rate' => $request->input('daily_rate', 0),
            'dependents_count' => $request->input('dependents_count', 0),
        ];
    }
}
