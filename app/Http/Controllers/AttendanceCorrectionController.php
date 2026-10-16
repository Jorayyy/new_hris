<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\AuditLog;
use App\Services\DtrService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceCorrectionController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString() ?: AttendanceCorrection::STATUS_PENDING;

        $corrections = AttendanceCorrection::query()
            ->with(['employee', 'decider'])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('attendance.corrections', compact('corrections', 'status'));
    }

    public function decide(Request $request, AttendanceCorrection $correction): RedirectResponse
    {
        abort_unless($correction->status === AttendanceCorrection::STATUS_PENDING, 403);

        $data = $request->validate([
            'decision' => ['required', 'in:approve,reject'],
            'decision_remarks' => ['nullable', 'string', 'max:255'],
        ]);

        if ($data['decision'] === 'approve') {
            $attendance = Attendance::where('employee_id', $correction->employee_id)
                ->whereDate('work_date', $correction->work_date->toDateString())
                ->first() ?? new Attendance([
                    'employee_id' => $correction->employee_id,
                    'work_date' => $correction->work_date->toDateString(),
                ]);

            $attendance->fill(collect(AttendanceCorrection::PUNCHES)
                ->mapWithKeys(fn ($punch) => [$punch => $correction->{$punch}])->all());
            $attendance->branch_id ??= $correction->employee?->branch_id;
            $attendance->source = 'correction';
            $attendance->is_manual = true;
            $attendance->save();

            (new DtrService)->process($attendance->fresh(['employee', 'shift']));

            $correction->status = AttendanceCorrection::STATUS_APPROVED;
        } else {
            $correction->status = AttendanceCorrection::STATUS_REJECTED;
        }

        $correction->decided_by = $request->user()->id;
        $correction->decided_at = now();
        $correction->decision_remarks = $data['decision_remarks'] ?? null;
        $correction->save();

        AuditLog::record('timekeeping', 'DTR correction for '.$correction->employee?->full_name.' ('.$correction->work_date->toDateString().') '.$correction->status.' by '.$request->user()->name, $correction);

        return back()->with('ok', 'Correction '.$correction->status.'.');
    }
}
