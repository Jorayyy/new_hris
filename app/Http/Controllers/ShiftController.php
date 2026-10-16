<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShiftController extends Controller
{
    public function index(): View
    {
        $shifts = Shift::withCount('employeeShifts')->orderBy('name')->paginate(15);

        return view('shifts.index', compact('shifts'));
    }

    public function create(): View
    {
        return view('shifts.form', ['shift' => new Shift(['break_minutes' => 60, 'grace_minutes' => 0, 'required_hours' => 8, 'is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        Shift::create($this->validated($request));

        return redirect()->route('shifts.index')->with('ok', 'Shift created.');
    }

    public function edit(Shift $shift): View
    {
        return view('shifts.form', compact('shift'));
    }

    public function update(Request $request, Shift $shift): RedirectResponse
    {
        $shift->update($this->validated($request));

        return redirect()->route('shifts.index')->with('ok', 'Shift updated.');
    }

    public function destroy(Shift $shift): RedirectResponse
    {
        $shift->delete();

        return back()->with('ok', 'Shift deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'time_in' => ['required', 'date_format:H:i'],
            'time_out' => ['required', 'date_format:H:i'],
            'break_minutes' => ['required', 'integer', 'min:0', 'max:240'],
            'grace_minutes' => ['required', 'integer', 'min:0', 'max:120'],
            'required_hours' => ['required', 'numeric', 'min:1', 'max:24'],
            'is_night_diff' => ['boolean'],
            'is_active' => ['boolean'],
        ]) + [
            'is_night_diff' => $request->boolean('is_night_diff'),
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
