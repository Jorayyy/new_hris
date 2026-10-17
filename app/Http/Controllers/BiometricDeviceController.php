<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\BiometricDevice;
use App\Models\Branch;
use App\Models\Employee;
use App\Services\BiometricImportService;
use App\Services\DtrService;
use App\Support\TeamScope;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BiometricDeviceController extends Controller
{
    public function __construct(
        private readonly DtrService $dtr,
        private readonly BiometricImportService $import,
    ) {}

    public function index(): View
    {
        return view('biometric-devices.index', [
            'devices' => BiometricDevice::with('branch')->orderBy('name')->get(),
            'branches' => Branch::orderBy('name')->get(),
            'punches' => Attendance::where('source', 'biometric')
                ->with(['employee', 'branch'])
                ->latest('updated_at')
                ->limit(20)
                ->get(),
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt,dat,log', 'max:10240']]);

        $result = $this->import->parse((string) $request->file('file')->getRealPath());
        $user = $request->user();
        $allowed = TeamScope::employeeIds($user);
        $scoped = TeamScope::limited($user);
        $created = 0;
        $updated = 0;
        $problems = $result['problems'];
        $problemCount = count($problems);

        foreach ($result['rows'] as $row) {
            $employee = Employee::where('employee_no', $row['employee_no'])->where('is_active', true)->first();

            if (! $employee) {
                $problemCount++;
                if (count($problems) <= BiometricImportService::MAX_REPORTED) {
                    $problems[] = ['line' => $row['line'], 'raw' => $row['employee_no'].', '.$row['date'], 'reason' => 'Unknown employee no. (or inactive)'];
                }

                continue;
            }

            if ($scoped && ! in_array($employee->id, $allowed, true)) {
                $problemCount++;
                if (count($problems) <= BiometricImportService::MAX_REPORTED) {
                    $problems[] = ['line' => $row['line'], 'raw' => $row['employee_no'].', '.$row['date'], 'reason' => 'Outside your branch scope'];
                }

                continue;
            }

            // whereDate: work_date is a date column but the model's 'date' cast stores a datetime.
            $attendance = Attendance::where('employee_id', $employee->id)
                ->whereDate('work_date', $row['date'])
                ->first() ?? new Attendance([
                    'employee_id' => $employee->id,
                    'work_date' => $row['date'],
                ]);
            $existed = $attendance->exists;
            $attendance->branch_id ??= $employee->branch_id;
            $attendance->source = 'biometric';
            $attendance->time_in = Carbon::parse($row['date'].' '.$row['time_in']);
            $attendance->time_out = $row['time_out'] ? Carbon::parse($row['date'].' '.$row['time_out']) : null;
            $attendance->save();

            $this->dtr->process($attendance->fresh(['employee', 'shift']));
            $existed ? $updated++ : $created++;
        }

        return back()->with('ok', "Imported {$created} new and {$updated} existing day(s).")->with('import_report', [
            'created' => $created,
            'updated' => $updated,
            'problem_count' => $problemCount,
            'problems' => array_slice($problems, 0, BiometricImportService::MAX_REPORTED),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'branch_id' => ['nullable', 'exists:branches,id'],
        ]);

        $token = BiometricDevice::generateToken();

        BiometricDevice::create($data + ['token_hash' => BiometricDevice::hashToken($token)]);

        return back()->with('ok', 'Device registered.')->with('new_device_token', $token);
    }

    public function regenerate(BiometricDevice $device): RedirectResponse
    {
        $token = $device->issueToken();

        return back()->with('ok', 'Token regenerated.')->with('new_device_token', $token);
    }

    public function update(Request $request, BiometricDevice $device): RedirectResponse
    {
        $device->update(['is_active' => $request->boolean('is_active')]);

        return back()->with('ok', 'Device updated.');
    }

    public function destroy(BiometricDevice $device): RedirectResponse
    {
        $device->delete();

        return back()->with('ok', 'Device removed.');
    }
}
