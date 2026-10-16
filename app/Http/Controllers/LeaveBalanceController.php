<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaveBalanceController extends Controller
{
    public function index(Request $request): View
    {
        $year = $request->integer('year') ?: (int) now()->year;

        $balances = LeaveBalance::query()
            ->with(['employee', 'leaveType'])
            ->where('year', $year)
            ->when($request->filled('employee'), fn ($q) => $q->where('employee_id', $request->integer('employee')))
            ->when($request->filled('type'), fn ($q) => $q->where('leave_type_id', $request->integer('type')))
            ->join('employees', 'employees.id', '=', 'leave_balances.employee_id')
            ->orderBy('employees.last_name')
            ->orderBy('leave_type_id')
            ->select('leave_balances.*')
            ->paginate(30)
            ->withQueryString();

        return view('leave-balances.index', [
            'balances' => $balances,
            'year' => $year,
            'employees' => Employee::where('is_active', true)->orderBy('last_name')->get(),
            'leaveTypes' => LeaveType::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'leave_type_id' => ['required', 'exists:leave_types,id'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'credits' => ['required', 'numeric', 'min:0'],
        ]);

        $balance = LeaveBalance::updateOrCreate(
            ['employee_id' => $data['employee_id'], 'leave_type_id' => $data['leave_type_id'], 'year' => $data['year']],
            ['credits' => $data['credits']]
        );

        AuditLog::record('update', 'Set leave credits for '.$balance->employee?->full_name.' ('.$data['credits'].' days)', $balance);

        return back()->with('ok', 'Leave credits saved.');
    }

    public function update(Request $request, LeaveBalance $leaveBalance): RedirectResponse
    {
        $data = $request->validate(['credits' => ['required', 'numeric', 'min:0']]);
        $leaveBalance->update(['credits' => $data['credits']]);

        AuditLog::record('update', 'Adjusted leave credits for '.$leaveBalance->employee?->full_name.' to '.$data['credits'].' days', $leaveBalance);

        return back()->with('ok', 'Leave credits updated.');
    }

    public function accrue(Request $request): RedirectResponse
    {
        $year = (int) $request->input('year', now()->year);
        $types = LeaveType::where('is_active', true)->get();
        $created = 0;

        foreach (Employee::where('is_active', true)->get() as $employee) {
            foreach ($types as $type) {
                $balance = LeaveBalance::firstOrCreate(
                    ['employee_id' => $employee->id, 'leave_type_id' => $type->id, 'year' => $year],
                    ['credits' => $type->max_days_per_year, 'used' => 0]
                );
                if ($balance->wasRecentlyCreated) {
                    $created++;
                }
            }
        }

        AuditLog::record('update', "Ran {$year} leave accrual ({$created} balances created)");

        return back()->with('ok', "Leave accrual for {$year} done. {$created} new balances created.");
    }
}
