<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\EmployeeShift;
use App\Models\ScheduleChangeRequest;
use App\Models\Shift;
use App\Support\Notify;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ScheduleChangeRequestController extends Controller
{
    public function mine(Request $request): View
    {
        $employee = $request->user()->employee;
        abort_unless($employee, 404, 'No employee record linked to your account.');

        return view('me.schedule', [
            'requests' => $employee->scheduleChangeRequests()->with(['currentShift', 'shift'])->latest()->get(),
            'shifts' => Shift::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $employee = $request->user()->employee;
        abort_unless($employee, 404);

        $data = $request->validate([
            'work_date' => ['required', 'date'],
            'shift_id' => ['required', 'exists:shifts,id'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $change = $employee->scheduleChangeRequests()->create([
            'work_date' => $data['work_date'],
            'current_shift_id' => $employee->shiftForDate($data['work_date'])?->id,
            'shift_id' => $data['shift_id'],
            'reason' => $data['reason'] ?? null,
            'status' => ScheduleChangeRequest::STATUS_PENDING,
        ]);

        AuditLog::record('scheduling', $employee->full_name.' filed a schedule change for '.$change->work_date->toDateString(), $change);

        Notify::approvers('Schedule change', $employee->full_name.' filed a schedule change for '.$change->work_date->toDateString().'.', route('schedule-changes.index'), '↻');

        return back()->with('ok', 'Schedule change submitted — awaiting approval.');
    }

    public function index(Request $request): View
    {
        $status = $request->string('status')->toString() ?: ScheduleChangeRequest::STATUS_PENDING;

        $requests = ScheduleChangeRequest::query()
            ->with(['employee', 'currentShift', 'shift', 'decider'])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('schedule-changes.index', compact('requests', 'status'));
    }

    public function decide(Request $request, ScheduleChangeRequest $change): RedirectResponse
    {
        abort_unless($change->status === ScheduleChangeRequest::STATUS_PENDING, 403);

        $data = $request->validate([
            'decision' => ['required', 'in:approve,reject'],
            'decision_remarks' => ['nullable', 'string', 'max:255'],
        ]);

        if ($data['decision'] === 'approve') {
            DB::transaction(function () use ($change) {
                EmployeeShift::create([
                    'employee_id' => $change->employee_id,
                    'shift_id' => $change->shift_id,
                    'effective_from' => $change->work_date,
                    'effective_to' => $change->work_date,
                    'published_at' => now(),
                ]);
            });
        }

        $change->status = $data['decision'] === 'approve'
            ? ScheduleChangeRequest::STATUS_APPROVED
            : ScheduleChangeRequest::STATUS_REJECTED;
        $change->decided_by = $request->user()->id;
        $change->decided_at = now();
        $change->decision_remarks = $data['decision_remarks'] ?? null;
        $change->save();

        AuditLog::record('scheduling', 'Schedule change for '.$change->employee?->full_name.' ('.$change->work_date->toDateString().') '.$change->status.' by '.$request->user()->name, $change);

        Notify::employee($change->employee, 'Schedule change '.$change->status, 'Your schedule change for '.$change->work_date->toDateString().' was '.$change->status.'.', route('me.schedule'), '↻');

        return back()->with('ok', 'Schedule change '.$change->status.'.');
    }
}
