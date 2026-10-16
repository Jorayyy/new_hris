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
    /** Maps a kiosk punch action to the attendance column it stamps. */
    private const PUNCHES = [
        'in' => 'time_in',
        'break1_out' => 'break1_out',
        'break1_in' => 'break1_in',
        'lunch_out' => 'lunch_out',
        'lunch_in' => 'lunch_in',
        'break2_out' => 'break2_out',
        'break2_in' => 'break2_in',
        'out' => 'time_out',
    ];

    public function punch(Request $request): RedirectResponse
    {
        if (! TimeClockIp::allows($request->ip())) {
            return back()->withInput($request->only('employee_no'))
                ->with('punch_error', 'Punching is only allowed from an approved company network.');
        }

        $data = $request->validate([
            'employee_no' => ['required', 'string'],
            'pin' => ['required', 'string'],
            'punch' => ['required', Rule::in(array_keys(self::PUNCHES))],
        ]);

        $employee = Employee::where('employee_no', $data['employee_no'])
            ->where('is_active', true)
            ->first();

        if (! $employee || ! $employee->pin || ! Hash::check($data['pin'], $employee->pin)) {
            return back()->withInput($request->only('employee_no'))
                ->with('punch_error', 'Invalid employee number or PIN.');
        }

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
        $attendance->{self::PUNCHES[$data['punch']]} = $now;
        $attendance->save();

        (new DtrService)->process($attendance->fresh(['employee', 'shift']));

        AuditLog::record('time-clock', $employee->full_name.' punched '.$label.' at '.$now->format('g:i:s A'), $attendance);

        return back()->with('punch_ok', $employee->full_name.' — '.$label.' at '.$now->format('g:i:s A'));
    }
}
