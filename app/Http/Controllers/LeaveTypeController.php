<?php

namespace App\Http\Controllers;

use App\Models\LeaveType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaveTypeController extends Controller
{
    public function index(): View
    {
        return view('leave-types.index', ['leaveTypes' => LeaveType::orderBy('name')->paginate(20)]);
    }

    public function create(): View
    {
        return view('leave-types.form', ['leaveType' => new LeaveType(['is_paid' => true, 'pay_percentage' => 100, 'is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        LeaveType::create($this->validated($request));

        return redirect()->route('leave-types.index')->with('ok', 'Leave type created.');
    }

    public function edit(LeaveType $leaveType): View
    {
        return view('leave-types.form', compact('leaveType'));
    }

    public function update(Request $request, LeaveType $leaveType): RedirectResponse
    {
        $leaveType->update($this->validated($request));

        return redirect()->route('leave-types.index')->with('ok', 'Leave type updated.');
    }

    public function destroy(LeaveType $leaveType): RedirectResponse
    {
        $leaveType->delete();

        return back()->with('ok', 'Leave type deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:30', 'unique:leave_types,code'.($request->route('leave_type') ? ','.$request->route('leave_type')->id : '')],
            'is_paid' => ['boolean'],
            'pay_percentage' => ['required', 'integer', 'min:0', 'max:100'],
            'max_days_per_year' => ['required', 'integer', 'min:0', 'max:365'],
            'is_cash_convertible' => ['boolean'],
            'requires_attachment' => ['boolean'],
            'is_active' => ['boolean'],
        ]) + [
            'is_paid' => $request->boolean('is_paid'),
            'is_cash_convertible' => $request->boolean('is_cash_convertible'),
            'requires_attachment' => $request->boolean('requires_attachment'),
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
