<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PinController extends Controller
{
    public function index(Request $request): View
    {
        return view('employees.pins', [
            'employees' => Employee::query()
                ->with('branch')
                ->when($request->filled('q'), function ($q) use ($request) {
                    $term = '%'.$request->string('q').'%';
                    $q->where(fn ($w) => $w->where('first_name', 'like', $term)->orWhere('last_name', 'like', $term)->orWhere('employee_no', 'like', $term));
                })
                ->when($request->filled('branch'), fn ($q) => $q->where('branch_id', $request->integer('branch')))
                ->when($request->filled('missing'), fn ($q) => $q->whereNull('pin'))
                ->orderBy('last_name')
                ->paginate(30)
                ->withQueryString(),
            'branches' => Branch::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $data = $request->validate(['pin' => ['nullable', 'digits:4']]);

        $employee->pin = $data['pin'] ?: null;
        $employee->clearPinLock();

        AuditLog::record('time-clock', 'Time-clock PIN '.($data['pin'] ? 'set' : 'cleared').' for '.$employee->full_name.' ('.$employee->employee_no.')', $employee);

        return back()->with('ok', 'PIN '.($data['pin'] ? 'updated' : 'cleared').' for '.$employee->full_name.'.');
    }

    public function generate(Employee $employee): RedirectResponse
    {
        $pin = $this->randomPin();

        $employee->pin = $pin;
        $employee->clearPinLock();

        AuditLog::record('time-clock', 'Time-clock PIN generated for '.$employee->full_name.' ('.$employee->employee_no.')', $employee);

        return back()
            ->with('ok', 'New PIN generated for '.$employee->full_name.'.')
            ->with('generated_pin', ['name' => $employee->full_name, 'pin' => $pin]);
    }

    private function randomPin(): string
    {
        return str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }
}
