<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\LoanPayment;
use App\Models\LoanType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeLoanController extends Controller
{
    public function index(Request $request): View
    {
        $loans = EmployeeLoan::query()
            ->with(['employee', 'loanType'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('loans.index', ['loans' => $loans]);
    }

    public function create(): View
    {
        return view('loans.form', [
            'loan' => new EmployeeLoan(['start_date' => now(), 'status' => 'active', 'terms_months' => 12]),
            'employees' => Employee::where('is_active', true)->orderBy('last_name')->get(),
            'loanTypes' => LoanType::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'loan_type_id' => ['required', 'exists:loan_types,id'],
            'principal' => ['required', 'numeric', 'min:1'],
            'terms_months' => ['required', 'integer', 'min:1', 'max:120'],
            'start_date' => ['required', 'date'],
        ]);

        EmployeeLoan::create($data + [
            'monthly_amortization' => round($data['principal'] / $data['terms_months'], 2),
            'balance' => $data['principal'],
            'status' => 'active',
        ]);

        return redirect()->route('loans.index')->with('ok', 'Loan recorded.');
    }

    public function show(EmployeeLoan $loan): View
    {
        $loan->load(['employee', 'loanType', 'payments']);

        return view('loans.show', compact('loan'));
    }

    public function payment(Request $request, EmployeeLoan $loan): RedirectResponse
    {
        $data = $request->validate(['amount' => ['required', 'numeric', 'min:1'], 'paid_at' => ['required', 'date']]);

        LoanPayment::create($data + ['employee_loan_id' => $loan->id]);

        $balance = max(0, (float) $loan->balance - $data['amount']);
        $loan->update(['balance' => $balance, 'status' => $balance <= 0 ? 'paid' : 'active']);

        return back()->with('ok', 'Payment recorded.');
    }

    public function destroy(EmployeeLoan $loan): RedirectResponse
    {
        $loan->update(['status' => 'cancelled']);

        return back()->with('ok', 'Loan cancelled.');
    }
}
