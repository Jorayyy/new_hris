<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class LeaveRequestController extends Controller
{
    public function index(Request $request): View
    {
        $requests = LeaveRequest::query()
            ->with(['employee', 'leaveType', 'approver'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('employee'), fn ($q) => $q->where('employee_id', $request->integer('employee')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('leave-requests.index', [
            'requests' => $requests,
            'employees' => Employee::orderBy('last_name')->get(),
            'leaveTypes' => LeaveType::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'leave_type_id' => ['required', 'exists:leave_types,id'],
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
            'reason' => ['nullable', 'string'],
        ]);

        $type = LeaveType::findOrFail($data['leave_type_id']);
        $days = $this->workingDays($data['date_from'], $data['date_to']);

        LeaveRequest::create($data + [
            'days' => $days,
            'is_paid' => $type->is_paid,
            'status' => LeaveRequest::STATUS_PENDING,
        ]);

        return back()->with('ok', 'Leave request filed.');
    }

    public function export(Request $request): Response
    {
        $requests = LeaveRequest::query()
            ->with(['employee', 'leaveType'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('employee'), fn ($q) => $q->where('employee_id', $request->integer('employee')))
            ->latest()
            ->get();

        $rows = [['Employee No', 'Employee', 'Leave Type', 'From', 'To', 'Days', 'Paid', 'Status']];
        foreach ($requests as $leave) {
            $rows[] = [
                $leave->employee?->employee_no,
                $leave->employee?->full_name,
                $leave->leaveType?->name,
                optional($leave->date_from)->toDateString(),
                optional($leave->date_to)->toDateString(),
                number_format((float) $leave->days, 2, '.', ''),
                $leave->is_paid ? 'Yes' : 'No',
                $leave->status,
            ];
        }

        $csv = '';
        foreach ($rows as $row) {
            $csv .= implode(',', array_map(fn ($value) => '"'.str_replace('"', '""', (string) $value).'"', $row))."\r\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="leave-requests.csv"',
        ]);
    }

    public function approve(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        $request->validate(['status' => ['required', 'in:approved,rejected']]);

        $status = $request->string('status')->toString();
        $leaveRequest->update([
            'status' => $status,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        if ($status === LeaveRequest::STATUS_APPROVED) {
            $this->applyToBalances($leaveRequest);
            $this->markAttendanceAsLeave($leaveRequest);
        }

        AuditLog::record($status, ucfirst($status).' leave request #'.$leaveRequest->id, $leaveRequest);

        return back()->with('ok', "Leave request {$status}.");
    }

    public function destroy(LeaveRequest $leaveRequest): RedirectResponse
    {
        $leaveRequest->update(['status' => LeaveRequest::STATUS_CANCELLED]);
        AuditLog::record('cancel', 'Cancelled leave request #'.$leaveRequest->id, $leaveRequest);

        return back()->with('ok', 'Leave request cancelled.');
    }

    private function applyToBalances(LeaveRequest $leaveRequest): void
    {
        $balance = LeaveBalance::firstOrCreate(
            [
                'employee_id' => $leaveRequest->employee_id,
                'leave_type_id' => $leaveRequest->leave_type_id,
                'year' => (int) Carbon::parse($leaveRequest->date_from)->year,
            ],
            ['credits' => 0, 'used' => 0]
        );
        $balance->increment('used', $leaveRequest->days);
    }

    private function markAttendanceAsLeave(LeaveRequest $leaveRequest): void
    {
        $cursor = Carbon::parse($leaveRequest->date_from);
        $end = Carbon::parse($leaveRequest->date_to);
        while ($cursor->lessThanOrEqualTo($end)) {
            if ($cursor->isWeekday()) {
                Attendance::updateOrCreate(
                    ['employee_id' => $leaveRequest->employee_id, 'work_date' => $cursor->toDateString()],
                    ['status' => Attendance::STATUS_LEAVE, 'remarks' => 'On approved leave', 'source' => 'manual']
                );
            }
            $cursor->addDay();
        }
    }

    private function workingDays(string $from, string $to): int
    {
        $cursor = Carbon::parse($from);
        $end = Carbon::parse($to);
        $days = 0;
        while ($cursor->lessThanOrEqualTo($end)) {
            if ($cursor->isWeekday()) {
                $days++;
            }
            $cursor->addDay();
        }

        return max(1, $days);
    }
}
