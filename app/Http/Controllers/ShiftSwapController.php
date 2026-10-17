<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeShift;
use App\Models\ShiftSwapRequest;
use App\Support\Notify;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ShiftSwapController extends Controller
{
    public function mine(Request $request): View
    {
        $employee = $request->user()->employee;
        abort_unless($employee, 404, 'No employee record linked to your account.');

        return view('me.swaps', [
            'requests' => $employee->shiftSwapRequests()->with(['target', 'requesterShift', 'targetShift'])->latest()->get(),
            'colleagues' => Employee::where('is_active', true)->whereKeyNot($employee->id)->orderBy('last_name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $employee = $request->user()->employee;
        abort_unless($employee, 404);

        $data = $request->validate([
            'target_id' => ['required', 'exists:employees,id', Rule::notIn([$employee->id])],
            'swap_date' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $mine = $employee->shiftForDate($data['swap_date']);
        $theirs = Employee::findOrFail($data['target_id'])->shiftForDate($data['swap_date']);

        if (! $mine || ! $theirs) {
            return back()->withErrors(['swap_date' => 'Both employees must have a shift on that date.'])->withInput();
        }

        ShiftSwapRequest::create([
            'requester_id' => $employee->id,
            'target_id' => $data['target_id'],
            'swap_date' => $data['swap_date'],
            'requester_shift_id' => $mine->id,
            'target_shift_id' => $theirs->id,
            'reason' => $data['reason'] ?? null,
            'status' => ShiftSwapRequest::STATUS_PENDING,
        ]);

        Notify::approvers('Shift swap', $employee->full_name.' requested a shift swap for '.$data['swap_date'].'.', route('swaps.index'), '⇄');

        return back()->with('ok', 'Shift swap requested.');
    }

    public function index(): View
    {
        return view('swaps.index', [
            'requests' => ShiftSwapRequest::with(['requester', 'target', 'requesterShift', 'targetShift', 'decider'])
                ->latest()
                ->paginate(20),
        ]);
    }

    public function decide(Request $request, ShiftSwapRequest $swap): RedirectResponse
    {
        $data = $request->validate(['decision' => ['required', 'in:approve,reject']]);

        abort_unless($swap->status === ShiftSwapRequest::STATUS_PENDING, 403, 'This request is already decided.');

        if ($data['decision'] === 'approve') {
            DB::transaction(function () use ($swap) {
                foreach ([
                    [$swap->requester_id, $swap->target_shift_id],
                    [$swap->target_id, $swap->requester_shift_id],
                ] as [$employeeId, $shiftId]) {
                    EmployeeShift::create([
                        'employee_id' => $employeeId,
                        'shift_id' => $shiftId,
                        'effective_from' => $swap->swap_date,
                        'effective_to' => $swap->swap_date,
                        'published_at' => now(),
                    ]);
                }
            });
        }

        $swap->update([
            'status' => $data['decision'] === 'approve' ? ShiftSwapRequest::STATUS_APPROVED : ShiftSwapRequest::STATUS_REJECTED,
            'decided_by' => $request->user()->id,
            'decided_at' => now(),
        ]);

        foreach ([$swap->requester, $swap->target] as $party) {
            Notify::employee($party, 'Shift swap '.$swap->status, 'Your shift swap for '.$swap->swap_date->toDateString().' was '.$swap->status.'.', route('me.swaps'), '⇄');
        }

        return back()->with('ok', 'Shift swap '.($data['decision'] === 'approve' ? 'approved.' : 'rejected.'));
    }
}
