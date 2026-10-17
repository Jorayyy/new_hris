<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use App\Services\DtrService;
use App\Support\TeamScope;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    /** Punch columns, in punch order, edited as time-of-day on the work date. */
    private const PUNCH_FIELDS = [
        'time_in', 'break1_out', 'break1_in', 'lunch_out',
        'lunch_in', 'break2_out', 'break2_in', 'time_out',
    ];

    public function __construct(private readonly DtrService $dtr) {}

    public function index(Request $request): View
    {
        $from = $request->date('from') ?? now()->startOfMonth();
        $to = $request->date('to') ?? now();
        $user = $request->user();

        $base = TeamScope::apply(Attendance::query(), $user)
            ->when($request->filled('employee'), fn ($q) => $q->where('employee_id', $request->integer('employee')))
            ->when($request->filled('branch'), fn ($q) => $q->where('branch_id', $request->integer('branch')))
            ->whereBetween('work_date', [$from->toDateString(), $to->toDateString()]);

        $attendances = (clone $base)
            ->with(['employee', 'shift'])
            ->orderByDesc('work_date')
            ->paginate(25)
            ->withQueryString();

        $totals = [
            'present' => (clone $base)->where('status', Attendance::STATUS_PRESENT)->count(),
            'hours' => (float) (clone $base)->sum('hours_worked'),
            'late' => (int) (clone $base)->sum('late_minutes'),
            'undertime' => (int) (clone $base)->sum('undertime_minutes'),
            'overtime' => (float) (clone $base)->sum('overtime_hours'),
            'night' => (float) (clone $base)->sum('night_diff_hours'),
        ];

        return view('attendance.index', [
            'attendances' => $attendances,
            'employees' => TeamScope::employees($user),
            'branches' => TeamScope::branches($user),
            'from' => $from,
            'to' => $to,
            'totals' => $totals,
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

        $attendances = TeamScope::apply(Attendance::query(), $request->user())
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
        TeamScope::authorize(auth()->user(), $attendance->branch_id);

        return view('attendance.form', $this->formData($attendance));
    }

    public function update(Request $request, Attendance $attendance): RedirectResponse
    {
        TeamScope::authorize($request->user(), $attendance->branch_id);
        $attendance->update($this->validated($request));
        $this->dtr->process($attendance->fresh(['employee']));

        return redirect()->route('attendance.index')->with('ok', 'Attendance updated.');
    }

    public function destroy(Attendance $attendance): RedirectResponse
    {
        TeamScope::authorize(auth()->user(), $attendance->branch_id);
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
        $allowed = TeamScope::employeeIds($request->user());
        $scoped = TeamScope::limited($request->user());
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
            if (! $employee || ! $date || ($scoped && ! in_array($employee->id, $allowed, true))) {
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
            'employees' => TeamScope::employees(auth()->user()),
            'branches' => TeamScope::branches(auth()->user()),
        ];
    }

    private function validated(Request $request): array
    {
        $rules = [
            'employee_id' => ['required', 'exists:employees,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'work_date' => ['required', 'date'],
            'status' => ['required', 'in:present,absent,leave,holiday'],
            'remarks' => ['nullable', 'string', 'max:255'],
        ];
        foreach (self::PUNCH_FIELDS as $field) {
            $rules[$field] = ['nullable', 'date_format:H:i'];
        }

        $data = $request->validate($rules);

        TeamScope::authorizeEmployee($request->user(), Employee::find($data['employee_id']));

        foreach (self::PUNCH_FIELDS as $field) {
            $data[$field] = empty($data[$field]) ? null : Carbon::parse($data['work_date'].' '.$data[$field]);
        }

        return $data + ['is_manual' => true, 'source' => 'manual'];
    }
}
