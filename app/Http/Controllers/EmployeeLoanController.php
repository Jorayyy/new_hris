<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\LoanType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeLoanController extends Controller
{
    public function index(): View
    {
        return view('loans.index', [
            'loans' => EmployeeLoan::with(['employee', 'type'])->latest()->paginate(20),
            'employees' => Employee::where('is_active', true)->orderBy('last_name')->get(),
            'loanTypes' => LoanType::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'loan_type_id' => ['required', 'exists:loan_types,id'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'principal' => ['required', 'numeric', 'min:1'],
            'monthly_amortization' => ['required', 'numeric', 'min:1'],
            'start_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        EmployeeLoan::create($data + [
            'balance' => $data['principal'],
            'status' => EmployeeLoan::STATUS_ACTIVE,
        ]);

        return back()->with('ok', 'Employee loan created.');
    }

    public function destroy(EmployeeLoan $loan): RedirectResponse
    {
        $loan->delete();

        return back()->with('ok', 'Employee loan deleted.');
    }
}
