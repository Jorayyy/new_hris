<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
        $employee->load(['branch', 'department', 'position', 'documents', 'allowances', 'deductions']);

        return view('employees.show', [
            'employee' => $employee,
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

        $rows = [['Employee No', 'Name', 'Branch', 'Department', 'Position', 'Status', 'Date Hired', 'Basic Salary']];
        foreach ($employees as $employee) {
            $rows[] = [
                $employee->employee_no,
                $employee->full_name,
                $employee->branch?->name,
                $employee->department?->name,
                $employee->position?->title,
                $employee->employment_status,
                optional($employee->date_hired)->toDateString(),
                number_format((float) $employee->basic_salary, 2, '.', ''),
            ];
        }

        return $this->csv($rows, 'employees.csv');
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
            'is_active' => ['boolean'],
        ]);

        if (empty($data['pin'])) {
            unset($data['pin']);
        }

        return $data + [
            'is_active' => $request->boolean('is_active'),
            'monthly_allowance' => $request->input('monthly_allowance', 0),
            'daily_rate' => $request->input('daily_rate', 0),
            'dependents_count' => $request->input('dependents_count', 0),
        ];
    }
}
