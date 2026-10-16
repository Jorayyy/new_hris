<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Position;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PositionController extends Controller
{
    public function index(): View
    {
        $positions = Position::with('department')->withCount('employees')->orderBy('title')->paginate(15);

        return view('positions.index', compact('positions'));
    }

    public function create(): View
    {
        return view('positions.form', [
            'position' => new Position,
            'departments' => Department::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Position::create($this->validated($request));

        return redirect()->route('positions.index')->with('ok', 'Position created.');
    }

    public function edit(Position $position): View
    {
        return view('positions.form', [
            'position' => $position,
            'departments' => Department::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Position $position): RedirectResponse
    {
        $position->update($this->validated($request));

        return redirect()->route('positions.index')->with('ok', 'Position updated.');
    }

    public function destroy(Position $position): RedirectResponse
    {
        $position->delete();

        return back()->with('ok', 'Position deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'department_id' => ['nullable', 'exists:departments,id'],
            'title' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'default_salary' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ]) + ['is_active' => $request->boolean('is_active')];
    }
}
