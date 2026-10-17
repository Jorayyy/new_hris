<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Support\TeamScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceExceptionController extends Controller
{
    public function index(Request $request): View
    {
        $from = $request->date('from') ?? now()->startOfMonth();
        $to = $request->date('to') ?? now();
        $type = $request->string('type')->toString() ?: 'all';
        $user = $request->user();

        $scope = TeamScope::apply(Attendance::query(), $user)
            ->whereBetween('work_date', [$from->toDateString(), $to->toDateString()])
            ->when($request->filled('branch'), fn ($q) => $q->where('branch_id', $request->integer('branch')))
            ->when($request->filled('employee'), fn ($q) => $q->where('employee_id', $request->integer('employee')));

        $totals = [
            'late' => (clone $scope)->where('late_minutes', '>', 0)->count(),
            'undertime' => (clone $scope)->where('undertime_minutes', '>', 0)->count(),
            'absent' => (clone $scope)->where('status', Attendance::STATUS_ABSENT)->count(),
            'incomplete' => (clone $scope)->whereNotNull('time_in')->whereNull('time_out')->count(),
        ];

        $rows = (clone $scope)
            ->when($type === 'late', fn ($q) => $q->where('late_minutes', '>', 0))
            ->when($type === 'undertime', fn ($q) => $q->where('undertime_minutes', '>', 0))
            ->when($type === 'absent', fn ($q) => $q->where('status', Attendance::STATUS_ABSENT))
            ->when($type === 'incomplete', fn ($q) => $q->whereNotNull('time_in')->whereNull('time_out'))
            ->when($type === 'all', fn ($q) => $q->where(fn (Builder $inner) => $inner
                ->where('late_minutes', '>', 0)
                ->orWhere('undertime_minutes', '>', 0)
                ->orWhere('status', Attendance::STATUS_ABSENT)
                ->orWhere(fn (Builder $p) => $p->whereNotNull('time_in')->whereNull('time_out'))))
            ->with(['employee', 'branch'])
            ->orderByDesc('work_date')
            ->paginate(50)
            ->withQueryString();

        return view('attendance.exceptions', [
            'rows' => $rows,
            'from' => $from,
            'to' => $to,
            'type' => $type,
            'totals' => $totals,
            'branches' => TeamScope::branches($user),
            'employees' => TeamScope::employees($user),
        ]);
    }
}
