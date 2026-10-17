<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\OvertimeRequest;
use App\Support\Notify;
use App\Support\TeamScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OvertimeRequestController extends Controller
{
    public function mine(Request $request): View
    {
        $employee = $request->user()->employee;
        abort_unless($employee, 404, 'No employee record linked to your account.');

        return view('me.ot', [
            'requests' => $employee->overtimeRequests()->latest()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $employee = $request->user()->employee;
        abort_unless($employee, 404);

        $data = $request->validate([
            'work_date' => ['required', 'date'],
            'hours' => ['required', 'numeric', 'min:0.5', 'max:12'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $overtime = $employee->overtimeRequests()->create($data + [
            'status' => OvertimeRequest::STATUS_PENDING,
        ]);

        AuditLog::record('timekeeping', $employee->full_name.' filed '.$data['hours'].'h overtime for '.$overtime->work_date->toDateString(), $overtime);

        Notify::approvers('Overtime filed', $employee->full_name.' filed '.$data['hours'].'h overtime for '.$overtime->work_date->toDateString().'.', route('ot.index'), '↑', $employee);

        return back()->with('ok', 'Overtime filing submitted — awaiting approval.');
    }

    public function index(Request $request): View
    {
        $status = $request->string('status')->toString() ?: OvertimeRequest::STATUS_PENDING;

        $requests = TeamScope::applyEmployee(OvertimeRequest::query(), $request->user())
            ->with(['employee', 'decider'])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('ot.index', compact('requests', 'status'));
    }

    public function decide(Request $request, OvertimeRequest $overtime): RedirectResponse
    {
        TeamScope::authorizeEmployee($request->user(), $overtime->employee);

        abort_unless($overtime->status === OvertimeRequest::STATUS_PENDING, 403);

        $data = $request->validate([
            'decision' => ['required', 'in:approve,reject'],
            'decision_remarks' => ['nullable', 'string', 'max:255'],
        ]);

        $overtime->status = $data['decision'] === 'approve'
            ? OvertimeRequest::STATUS_APPROVED
            : OvertimeRequest::STATUS_REJECTED;
        $overtime->decided_by = $request->user()->id;
        $overtime->decided_at = now();
        $overtime->decision_remarks = $data['decision_remarks'] ?? null;
        $overtime->save();

        AuditLog::record('timekeeping', 'Overtime filing for '.$overtime->employee?->full_name.' ('.$overtime->work_date->toDateString().') '.$overtime->status.' by '.$request->user()->name, $overtime);

        Notify::employee($overtime->employee, 'Overtime '.$overtime->status, 'Your overtime filing for '.$overtime->work_date->toDateString().' was '.$overtime->status.'.', route('me.ot'), '↑');

        return back()->with('ok', 'Overtime '.$overtime->status.'.');
    }
}
