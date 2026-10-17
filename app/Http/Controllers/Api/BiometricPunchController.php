<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\BiometricDevice;
use App\Models\Employee;
use App\Services\DtrService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BiometricPunchController extends Controller
{
    public function __construct(private readonly DtrService $dtr) {}

    public function store(Request $request): JsonResponse
    {
        $device = BiometricDevice::findByToken((string) $request->header('X-Device-Token'));

        if (! $device) {
            return response()->json(['ok' => false, 'error' => 'Invalid or inactive device token.'], 401);
        }

        $data = $request->validate([
            'employee_no' => ['required_without:punches', 'string'],
            'punch' => ['required_without:punches', Rule::in(array_keys(Attendance::PUNCHES))],
            'timestamp' => ['nullable', 'date'],
            'punches' => ['sometimes', 'array'],
            'punches.*.employee_no' => ['required', 'string'],
            'punches.*.punch' => ['required', Rule::in(array_keys(Attendance::PUNCHES))],
            'punches.*.timestamp' => ['nullable', 'date'],
        ]);

        $events = $data['punches'] ?? [[
            'employee_no' => $data['employee_no'],
            'punch' => $data['punch'],
            'timestamp' => $data['timestamp'] ?? null,
        ]];

        $processed = 0;
        $skipped = 0;

        foreach ($events as $event) {
            $employee = Employee::where('employee_no', trim((string) $event['employee_no']))
                ->where('is_active', true)
                ->first();

            if (! $employee) {
                $skipped++;

                continue;
            }

            $at = ! empty($event['timestamp']) ? Carbon::parse($event['timestamp']) : now();

            $attendance = Attendance::where('employee_id', $employee->id)
                ->whereDate('work_date', $at->toDateString())
                ->first() ?? new Attendance([
                    'employee_id' => $employee->id,
                    'work_date' => $at->toDateString(),
                ]);

            $attendance->branch_id ??= $employee->branch_id;
            $attendance->source = 'biometric';
            $attendance->{Attendance::PUNCHES[$event['punch']]} = $at;
            $attendance->save();

            $this->dtr->process($attendance->fresh(['employee', 'shift']));
            $processed++;
        }

        $device->forceFill(['last_seen_at' => now()])->save();

        return response()->json(['ok' => true, 'processed' => $processed, 'skipped' => $skipped]);
    }
}
