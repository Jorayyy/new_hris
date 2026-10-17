<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeShift;
use App\Models\Shift;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class RosterController extends Controller
{
    public function index(): View
    {
        return view('roster.index', [
            'assignments' => EmployeeShift::with(['employee', 'shift'])
                ->join('employees', 'employees.id', '=', 'employee_shifts.employee_id')
                ->orderBy('employees.last_name')
                ->orderByDesc('employee_shifts.effective_from')
                ->select('employee_shifts.*')
                ->paginate(20),
            'employees' => Employee::where('is_active', true)->orderBy('last_name')->get(),
            'shifts' => Shift::where('is_active', true)->orderBy('name')->get(),
            'pending' => EmployeeShift::whereNull('published_at')->count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'shift_id' => ['required', 'exists:shifts,id'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
        ]);

        EmployeeShift::where('employee_id', $data['employee_id'])
            ->whereNull('effective_to')
            ->where('effective_from', '<', $data['effective_from'])
            ->update(['effective_to' => Carbon::parse($data['effective_from'])->subDay()->toDateString()]);

        EmployeeShift::create($data);

        return back()->with('ok', 'Shift assigned.');
    }

    public function publish(): RedirectResponse
    {
        $count = EmployeeShift::whereNull('published_at')->update(['published_at' => now()]);

        return back()->with('ok', "Published {$count} shift assignment(s).");
    }

    public function destroy(EmployeeShift $assignment): RedirectResponse
    {
        $assignment->delete();

        return back()->with('ok', 'Shift assignment removed.');
    }
}
