<?php

namespace App\Http\Controllers;

use App\Models\LoanType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoanTypeController extends Controller
{
    public function index(): View
    {
        return view('loan-types.index', ['loanTypes' => LoanType::withCount('loans')->orderBy('name')->paginate(20)]);
    }

    public function create(): View
    {
        return view('loan-types.form', ['loanType' => new LoanType(['is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        LoanType::create($this->validated($request));

        return redirect()->route('loan-types.index')->with('ok', 'Loan type created.');
    }

    public function edit(LoanType $loanType): View
    {
        return view('loan-types.form', compact('loanType'));
    }

    public function update(Request $request, LoanType $loanType): RedirectResponse
    {
        $loanType->update($this->validated($request));

        return redirect()->route('loan-types.index')->with('ok', 'Loan type updated.');
    }

    public function destroy(LoanType $loanType): RedirectResponse
    {
        $loanType->delete();

        return back()->with('ok', 'Loan type deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:30', 'unique:loan_types,code'.($request->route('loan_type') ? ','.$request->route('loan_type')->id : '')],
            'interest_rate' => ['required', 'numeric', 'min:0', 'max:1'],
            'max_amount' => ['required', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ]) + ['is_active' => $request->boolean('is_active')];
    }
}
