<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\TimeClockIp;
use App\Services\DtrService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TimeClockController extends Controller
{
    public function punch(Request $request): RedirectResponse|JsonResponse
    {
        if (! TimeClockIp::allows($request->ip())) {
            return $this->fail($request, 'Punching is only allowed from an approved company network.', 403);
        }

        $data = $request->validate([
            'employee_no' => ['required', 'string'],
            'pin' => ['required', 'string'],
            'punch' => ['required', Rule::in(array_keys(Attendance::PUNCHES))],
            'punch_at' => ['nullable', 'date'],
            'request_id' => ['nullable', 'string', 'max:64'],
        ]);

        $now = $this->resolveTapTime($data['punch_at'] ?? null);
        $label = strtoupper(str_replace('_', ' ', $data['punch']));
        $column = Attendance::PUNCHES[$data['punch']];

        $employee = Employee::where('employee_no', $data['employee_no'])
            ->where('is_active', true)
            ->first();

        if ($employee && $employee->isPinLocked()) {
            return $this->fail($request, 'Too many failed attempts. Try again after '.$employee->pin_locked_until->format('g:i A').'.', 429);
        }

        if (! $employee || ! $employee->checkPin($data['pin'])) {
            $employee?->registerFailedPin();

            return $this->fail($request, 'Invalid employee number or PIN.', 422);
        }

        $employee->clearPinLock();

        $shift = $employee->shiftForDate($now->toDateString());

        try {
            $attendance = Attendance::where('employee_id', $employee->id)
                ->whereDate('work_date', $now->toDateString())
                ->first() ?? new Attendance([
                    'employee_id' => $employee->id,
                    'work_date' => $now->toDateString(),
                    'branch_id' => $employee->branch_id,
                    'source' => 'web',
                    'shift_id' => $shift?->id,
                ]);

            if ($attendance->{$column} !== null) {
                return $this->alreadyPunched($request, $attendance, $column, $now, $label);
            }

            $attendance->branch_id ??= $employee->branch_id;
            $attendance->shift_id ??= $shift?->id;
            $attendance->source = 'web';
            $attendance->{$column} = $now;
            $attendance->save();
        } catch (QueryException $e) {
            if (! $this->isDuplicateKey($e)) {
                throw $e;
            }

            $attendance = Attendance::where('employee_id', $employee->id)
                ->whereDate('work_date', $now->toDateString())
                ->first();

            if ($attendance && $attendance->{$column} !== null) {
                return $this->alreadyPunched($request, $attendance, $column, $now, $label);
            }

            throw $e;
        }

        (new DtrService)->process($attendance->fresh(['employee', 'shift']));

        $message = $employee->full_name.' — '.$label.' at '.$now->format('g:i:s A');

        AuditLog::record('time-clock', $employee->full_name.' punched '.$label.' at '.$now->format('g:i:s A'), $attendance);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => $message,
                'at' => $now->toIso8601String(),
            ]);
        }

        return back()->with('punch_ok', $message);
    }

    /**
     * Accept the time the employee actually tapped, so retries that wait for a
     * free database slot still land on the correct DTR minute. Only a small
     * window around the server clock is trusted; anything else uses server time.
     */
    private function resolveTapTime(?string $value): Carbon
    {
        $now = now();

        if (! $value) {
            return $now;
        }

        try {
            $tap = Carbon::parse($value)->setTimezone(config('app.timezone'));
        } catch (\Throwable) {
            return $now;
        }

        if ($tap->lt($now->copy()->subMinutes(10)) || $tap->gt($now->copy()->addMinute())) {
            return $now;
        }

        return $tap;
    }

    private function alreadyPunched(Request $request, Attendance $attendance, string $column, Carbon $tap, string $label): RedirectResponse|JsonResponse
    {
        $recorded = $attendance->{$column};

        $isRetry = $request->filled('request_id')
            && $recorded !== null
            && abs($recorded->diffInSeconds($tap)) <= 300;

        if ($isRetry && $request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'duplicate' => true,
                'message' => $attendance->employee?->full_name.' — '.$label.' already recorded at '.$recorded->format('g:i:s A'),
            ]);
        }

        return $this->fail($request, 'Already punched: '.$label.' is already recorded for today.', 409);
    }

    private function fail(Request $request, string $message, int $status): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['ok' => false, 'message' => $message], $status);
        }

        return back()->withInput($request->only('employee_no'))->with('punch_error', $message);
    }

    private function isDuplicateKey(QueryException $e): bool
    {
        return in_array((string) $e->getCode(), ['23000', '23505'], true)
            || str_contains(strtolower($e->getMessage()), 'duplicate');
    }
}
