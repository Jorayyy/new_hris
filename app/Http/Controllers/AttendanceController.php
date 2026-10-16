<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Employee;
use App\Services\DtrService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function __construct(private readonly DtrService $dtr) {}

    public function index(Request $request): View
    {
        $from = $request->date('from') ?? now()->startOfMonth();
        $to = $request->date('to') ?? now();

        $attendances = Attendance::query()
            ->with(['employee', 'shift'])
            ->when($request->filled('employee'), fn ($q) => $q->where('employee_id', $request->integer('employee')))
            ->when($request->filled('branch'), fn ($q) => $q->where('branch_id', $request->integer('branch')))
            ->whereBetween('work_date', [$from->toDateString(), $to->toDateString()])
            ->orderByDesc('work_date')
            ->paginate(25)
            ->withQueryString();

        return view('attendance.index', [
            'attendances' => $attendances,
            'employees' => Employee::where('is_active', true)->orderBy('last_name')->get(),
            'branches' => Branch::orderBy('name')->get(),
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function create(): View
    {
        return view('attendance.form', $this->formData(new Attendance(['work_date' => now(), 'status' => 'present', 'source' => 'manual', 'is_manual' => true])));
    }

    public function export(Request $request): Response
    {
        $from = $request->date('from') ?? now()->startOfMonth();
        $to = $request->date('to') ?? now();

        $attendances = Attendance::query()
            ->with(['employee', 'shift'])
            ->when($request->filled('employee'), fn ($q) => $q->where('employee_id', $request->integer('employee')))
            ->when($request->filled('branch'), fn ($q) => $q->where('branch_id', $request->integer('branch')))
            ->whereBetween('work_date', [$from->toDateString(), $to->toDateString()])
            ->orderByDesc('work_date')
            ->get();

        $rows = [['Employee No', 'Employee', 'Date', 'Status', 'Time In', 'Time Out', 'Hours', 'Late (min)', 'Undertime (min)', 'OT (hrs)', 'Night diff (hrs)']];
        foreach ($attendances as $attendance) {
            $rows[] = [
                $attendance->employee?->employee_no,
                $attendance->employee?->full_name,
                optional($attendance->work_date)->toDateString(),
                $attendance->status,
                optional($attendance->time_in)->format('H:i'),
                optional($attendance->time_out)->format('H:i'),
                number_format((float) $attendance->hours_worked, 2, '.', ''),
                $attendance->late_minutes,
                $attendance->undertime_minutes,
                number_format((float) $attendance->overtime_hours, 2, '.', ''),
                number_format((float) $attendance->night_diff_hours, 2, '.', ''),
            ];
        }

        $csv = '';
        foreach ($rows as $row) {
            $csv .= implode(',', array_map(fn ($value) => '"'.str_replace('"', '""', (string) $value).'"', $row))."\r\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="attendance.csv"',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $attendance = Attendance::create($data);
        $this->dtr->process($attendance);

        return redirect()->route('attendance.index')->with('ok', 'Attendance saved and processed.');
    }

    public function edit(Attendance $attendance): View
    {
        return view('attendance.form', $this->formData($attendance));
    }

    public function update(Request $request, Attendance $attendance): RedirectResponse
    {
        $attendance->update($this->validated($request));
        $this->dtr->process($attendance->fresh(['employee']));

        return redirect()->route('attendance.index')->with('ok', 'Attendance updated.');
    }

    public function destroy(Attendance $attendance): RedirectResponse
    {
        $attendance->delete();

        return back()->with('ok', 'Attendance deleted.');
    }

    public function importForm(): View
    {
        return view('attendance.import');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt']]);
        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $created = 0;
        $skipped = 0;
        $header = true;

        while (($row = fgetcsv($handle)) !== false) {
            if ($header) {
                $header = false;

                continue;
            }
            [$employeeNo, $date, $timeIn, $timeOut] = array_pad($row, 4, null);
            $employee = Employee::where('employee_no', trim((string) $employeeNo))->first();
            if (! $employee || ! $date) {
                $skipped++;

                continue;
            }

            $attendance = Attendance::updateOrCreate(
                ['employee_id' => $employee->id, 'work_date' => Carbon::parse($date)->toDateString()],
                [
                    'branch_id' => $employee->branch_id,
                    'time_in' => $timeIn ? Carbon::parse($date.' '.$timeIn) : null,
                    'time_out' => $timeOut ? Carbon::parse($date.' '.$timeOut) : null,
                    'source' => 'import',
                ]
            );
            $this->dtr->process($attendance);
            $created++;
        }
        fclose($handle);

        return redirect()->route('attendance.index')->with('ok', "Imported {$created} rows, skipped {$skipped}.");
    }

    private function formData(Attendance $attendance): array
    {
        return [
            'attendance' => $attendance,
            'employees' => Employee::where('is_active', true)->orderBy('last_name')->get(),
            'branches' => Branch::orderBy('name')->get(),
        ];
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'work_date' => ['required', 'date'],
            'time_in' => ['nullable', 'date'],
            'time_out' => ['nullable', 'date'],
            'status' => ['required', 'in:present,absent,leave,holiday'],
            'remarks' => ['nullable', 'string', 'max:255'],
        ]) + ['is_manual' => true, 'source' => 'manual'];
    }
}
