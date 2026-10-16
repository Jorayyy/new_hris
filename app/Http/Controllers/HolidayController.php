<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Holiday;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HolidayController extends Controller
{
    public function index(): View
    {
        $holidays = Holiday::with('branch')->orderByDesc('date')->paginate(20);

        return view('holidays.index', compact('holidays'));
    }

    public function create(): View
    {
        return view('holidays.form', [
            'holiday' => new Holiday(['type' => 'regular', 'rate_multiplier' => 2]),
            'branches' => Branch::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Holiday::create($this->validated($request));

        return redirect()->route('holidays.index')->with('ok', 'Holiday created.');
    }

    public function edit(Holiday $holiday): View
    {
        return view('holidays.form', [
            'holiday' => $holiday,
            'branches' => Branch::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Holiday $holiday): RedirectResponse
    {
        $holiday->update($this->validated($request));

        return redirect()->route('holidays.index')->with('ok', 'Holiday updated.');
    }

    public function destroy(Holiday $holiday): RedirectResponse
    {
        $holiday->delete();

        return back()->with('ok', 'Holiday deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'branch_id' => ['nullable', 'exists:branches,id'],
            'date' => ['required', 'date'],
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', 'in:regular,special'],
            'rate_multiplier' => ['required', 'numeric', 'min:1', 'max:5'],
        ]);
    }
}
