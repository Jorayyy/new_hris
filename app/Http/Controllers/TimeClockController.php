<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\TimeClockIp;
use App\Services\DtrService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class TimeClockController extends Controller
{
    public function punch(Request $request): RedirectResponse
    {
        if (! TimeClockIp::allows($request->ip())) {
            return back()->withInput($request->only('employee_no'))
                ->with('punch_error', 'Punching is only allowed from an approved company network.');
        }

        $data = $request->validate([
            'employee_no' => ['required', 'string'],
            'pin' => ['required', 'string'],
            'punch' => ['required', Rule::in(array_keys(Attendance::PUNCHES))],
        ]);

        $employee = Employee::where('employee_no', $data['employee_no'])
            ->where('is_active', true)
            ->first();

        if ($employee && $employee->isPinLocked()) {
            return back()->withInput($request->only('employee_no'))
                ->with('punch_error', 'Too many failed attempts. Try again after '.$employee->pin_locked_until->format('g:i A').'.');
        }

        if (! $employee || ! $employee->pin || ! Hash::check($data['pin'], $employee->pin)) {
            $employee?->registerFailedPin();

            return back()->withInput($request->only('employee_no'))
                ->with('punch_error', 'Invalid employee number or PIN.');
        }

        $employee->clearPinLock();

        $now = now();
        $label = strtoupper(str_replace('_', ' ', $data['punch']));

        $attendance = Attendance::where('employee_id', $employee->id)
            ->whereDate('work_date', $now->toDateString())
            ->first() ?? new Attendance([
                'employee_id' => $employee->id,
                'work_date' => $now->toDateString(),
            ]);

        $attendance->branch_id ??= $employee->branch_id;
        $attendance->source = 'kiosk';
        $attendance->{Attendance::PUNCHES[$data['punch']]} = $now;
        $attendance->save();

        (new DtrService)->process($attendance->fresh(['employee', 'shift']));

        AuditLog::record('time-clock', $employee->full_name.' punched '.$label.' at '.$now->format('g:i:s A'), $attendance);

        return back()->with('punch_ok', $employee->full_name.' — '.$label.' at '.$now->format('g:i:s A'));
    }
}
