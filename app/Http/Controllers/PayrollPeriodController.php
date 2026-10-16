<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\PayrollPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayrollPeriodController extends Controller
{
    public function index(): View
    {
        $periods = PayrollPeriod::with('branch')->withCount('runs')->orderByDesc('date_from')->paginate(15);

        return view('payroll.periods.index', compact('periods'));
    }

    public function create(): View
    {
        return view('payroll.periods.form', [
            'period' => new PayrollPeriod(['frequency' => 'semi_monthly', 'status' => 'open']),
            'branches' => Branch::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        PayrollPeriod::create($this->validated($request));

        return redirect()->route('payroll.periods.index')->with('ok', 'Pay period created.');
    }

    public function edit(PayrollPeriod $period): View
    {
        return view('payroll.periods.form', [
            'period' => $period,
            'branches' => Branch::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, PayrollPeriod $period): RedirectResponse
    {
        $period->update($this->validated($request));

        return redirect()->route('payroll.periods.index')->with('ok', 'Pay period updated.');
    }

    public function destroy(PayrollPeriod $period): RedirectResponse
    {
        $period->delete();

        return back()->with('ok', 'Pay period deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
            'pay_date' => ['required', 'date'],
            'frequency' => ['required', 'in:semi_monthly,monthly'],
            'status' => ['required', 'in:open,closed,processed'],
        ]);
    }
}
