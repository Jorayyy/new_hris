<?php

namespace App\Http\Controllers;

use App\Models\AttendanceCorrection;
use App\Models\AuditLog;
use App\Support\Notify;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TimekeepingController extends Controller
{
    public function index(Request $request): View
    {
        $employee = $request->user()->employee;
        abort_unless($employee, 404, 'No employee record linked to your account.');

        $date = $request->date('date');

        $existing = $date
            ? $employee->attendances()->whereDate('work_date', $date->toDateString())->first()
            : null;

        return view('me.tk', [
            'employee' => $employee,
            'date' => $date,
            'existing' => $existing,
            'corrections' => $employee->attendanceCorrections()->latest()->limit(30)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $employee = $request->user()->employee;
        abort_unless($employee, 404);

        $rules = [
            'work_date' => ['required', 'date', 'before_or_equal:today'],
            'reason' => ['required', 'string', 'max:500'],
        ];
        foreach (AttendanceCorrection::PUNCHES as $field) {
            $rules[$field] = ['nullable', 'date_format:H:i'];
        }

        $data = $request->validate($rules);

        if (! array_filter(array_intersect_key($data, array_flip(AttendanceCorrection::PUNCHES)))) {
            return back()->withInput()->withErrors(['time_in' => 'Enter at least one corrected punch.']);
        }

        foreach (AttendanceCorrection::PUNCHES as $field) {
            $data[$field] = empty($data[$field]) ? null : Carbon::parse($data['work_date'].' '.$data[$field]);
        }

        $correction = $employee->attendanceCorrections()->create($data + [
            'status' => AttendanceCorrection::STATUS_PENDING,
        ]);

        AuditLog::record('timekeeping', $employee->full_name.' requested a DTR correction for '.$correction->work_date->toDateString(), $correction);

        Notify::approvers('DTR correction', $employee->full_name.' requested a DTR correction for '.$correction->work_date->toDateString().'.', route('attendance.corrections.index'), '⚑');

        return back()->with('ok', 'Correction request submitted — HR will review it.');
    }
}
