<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SelfServiceController extends Controller
{
    public function dtr(Request $request): View
    {
        $employee = $request->user()->employee;
        abort_unless($employee, 404, 'No employee record linked to your account.');

        $from = $request->date('from') ?? now()->startOfMonth();
        $to = $request->date('to') ?? now();

        $attendances = $employee->attendances()
            ->with('shift')
            ->whereBetween('work_date', [$from->toDateString(), $to->toDateString()])
            ->orderByDesc('work_date')
            ->get();

        $totals = [
            'present' => $attendances->where('status', Attendance::STATUS_PRESENT)->count(),
            'hours' => (float) $attendances->sum('hours_worked'),
            'late' => (int) $attendances->sum('late_minutes'),
            'undertime' => (int) $attendances->sum('undertime_minutes'),
            'overtime' => (float) $attendances->sum('overtime_hours'),
            'night' => (float) $attendances->sum('night_diff_hours'),
        ];

        return view('me.dtr', compact('employee', 'attendances', 'from', 'to', 'totals'));
    }

    public function payslips(Request $request): View
    {
        $employee = $request->user()->employee;
        abort_unless($employee, 404, 'No employee record linked to your account.');

        $payslips = $employee->payslips()->with('run.period')->latest()->paginate(15);

        return view('me.payslips', compact('employee', 'payslips'));
    }

    public function payslip(Request $request, int $payslip): View
    {
        $employee = $request->user()->employee;
        abort_unless($employee, 404);

        $model = $employee->payslips()->with(['run.period', 'items'])->findOrFail($payslip);

        return view('payroll.payslips.show', ['payslip' => $model, 'readonly' => true]);
    }

    public function leave(Request $request): View
    {
        $employee = $request->user()->employee;
        abort_unless($employee, 404);

        return view('me.leave', [
            'employee' => $employee,
            'requests' => $employee->leaveRequests()->with('leaveType')->latest()->get(),
            'balances' => $employee->leaveBalances()->where('year', now()->year)->with('leaveType')->get(),
            'leaveTypes' => LeaveType::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function storeLeave(Request $request): RedirectResponse
    {
        $employee = $request->user()->employee;
        abort_unless($employee, 404);

        $data = $request->validate([
            'leave_type_id' => ['required', 'exists:leave_types,id'],
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
            'reason' => ['nullable', 'string'],
        ]);

        $type = LeaveType::findOrFail($data['leave_type_id']);

        $days = 0;
        $cursor = Carbon::parse($data['date_from']);
        $end = Carbon::parse($data['date_to']);
        while ($cursor->lessThanOrEqualTo($end)) {
            if ($cursor->isWeekday()) {
                $days++;
            }
            $cursor->addDay();
        }

        LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $type->id,
            'date_from' => $data['date_from'],
            'date_to' => $data['date_to'],
            'days' => max(1, $days),
            'is_paid' => $type->is_paid,
            'reason' => $data['reason'] ?? null,
            'status' => LeaveRequest::STATUS_PENDING,
        ]);

        return back()->with('ok', 'Leave request submitted.');
    }
}
