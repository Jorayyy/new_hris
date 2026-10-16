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
        return view('loans.types', [
            'loanTypes' => LoanType::withCount('loans')->orderBy('name')->paginate(20),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        LoanType::create($this->validated($request));

        return back()->with('ok', 'Loan type created.');
    }

    public function update(Request $request, LoanType $loanType): RedirectResponse
    {
        $loanType->update($this->validated($request));

        return back()->with('ok', 'Loan type updated.');
    }

    public function destroy(LoanType $loanType): RedirectResponse
    {
        if ($loanType->loans()->exists()) {
            return back()->with('error', 'Cannot delete a loan type that has employee loans.');
        }

        $loanType->delete();

        return back()->with('ok', 'Loan type deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'interest_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'is_government' => ['boolean'],
            'is_active' => ['boolean'],
        ]) + [
            'is_government' => $request->boolean('is_government'),
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
