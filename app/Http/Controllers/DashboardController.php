<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\User;
use App\Support\TeamScope;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        if ($user->hasRole(User::ROLE_EMPLOYEE)) {
            return $this->employeeDashboard($user);
        }

        $isHr = $user->hasRole(User::HR_ROLES);
        $isPayroll = $user->hasRole(User::PAYROLL_ROLES);
        $isManager = $user->hasRole(User::ROLE_MANAGER);
        $isAdmin = $user->hasRole([User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN]);

        $data = compact('user', 'isHr', 'isPayroll', 'isManager', 'isAdmin');

        $today = now();
        $date = $today->toDateString();
        $monthStart = $today->copy()->startOfMonth()->toDateString();

        if ($isHr || $isManager) {
            $data['board'] = [
                'headcount' => TeamScope::employees($user)->count(),
                'present' => TeamScope::apply(Attendance::whereDate('work_date', $date), $user)->where('status', Attendance::STATUS_PRESENT)->count(),
                'late' => TeamScope::apply(Attendance::whereDate('work_date', $date), $user)->where('late_minutes', '>', 0)->count(),
                'on_leave' => TeamScope::applyEmployee(LeaveRequest::where('status', LeaveRequest::STATUS_APPROVED)
                    ->whereDate('date_from', '<=', $date)->whereDate('date_to', '>=', $date), $user)->count(),
            ];
            $data['clockedIn'] = TeamScope::apply(Attendance::with('employee')
                ->whereDate('work_date', $date)->whereNotNull('time_in')->whereNull('time_out'), $user)
                ->latest('time_in')->take(8)->get();
            $data['pendingLeave'] = TeamScope::applyEmployee(LeaveRequest::with('employee')
                ->where('status', LeaveRequest::STATUS_PENDING), $user)->latest()->take(6)->get();
            $data['pendingLeaveCount'] = TeamScope::applyEmployee(LeaveRequest::where('status', LeaveRequest::STATUS_PENDING), $user)->count();
        }

        if ($isHr) {
            $data['anomalies'] = Attendance::query()
                ->select('employee_id',
                    DB::raw('SUM(late_minutes) as late_total'),
                    DB::raw('SUM(undertime_minutes) as undertime_total'))
                ->whereBetween('work_date', [$monthStart, $date])
                ->groupBy('employee_id')
                ->havingRaw('SUM(late_minutes) > 0 OR SUM(undertime_minutes) > 0')
                ->orderByDesc('late_total')
                ->take(6)->get()->load('employee');

            $data['regularization'] = Employee::where('is_active', true)
                ->where('employment_status', 'probationary')
                ->whereNotNull('date_hired')
                ->where('date_hired', '<=', $today->copy()->subMonths(5)->toDateString())
                ->orderBy('date_hired')->take(6)->get();
        }

        if ($isPayroll) {
            $data['trend'] = PayrollRun::with('period')->where('status', 'processed')
                ->latest('processed_at')->take(6)->get()->reverse()->values();
            $data['trendMax'] = max(1, (float) $data['trend']->max('net_total'));
            $data['nextPeriod'] = PayrollPeriod::where('status', 'open')->orderBy('date_from')->first();
            $data['lastRun'] = PayrollRun::with('period')->where('status', 'processed')->latest('processed_at')->first();
            $data['statutory'] = $this->statutoryDeadlines();
        }

        if (! $isManager) {
            $data['byBranch'] = Branch::withCount(['employees' => fn ($q) => $q->where('is_active', true)])
                ->orderByDesc('employees_count')->get();
            $data['branchMax'] = max(1, (int) $data['byBranch']->max('employees_count'));
            $data['byDept'] = Department::withCount(['employees' => fn ($q) => $q->where('is_active', true)])
                ->orderByDesc('employees_count')->take(6)->get();
            $data['deptMax'] = max(1, (int) $data['byDept']->max('employees_count'));

            $data['birthdays'] = Employee::where('is_active', true)->whereNotNull('birth_date')
                ->whereMonth('birth_date', (int) $today->month)->get()
                ->sortBy(fn ($e) => (int) $e->birth_date->day)->take(6)->values();

            $data['anniversaries'] = Employee::where('is_active', true)->whereNotNull('date_hired')
                ->whereMonth('date_hired', (int) $today->month)->get()
                ->map(fn ($e) => ['employee' => $e, 'years' => (int) $e->date_hired->diffInYears($today)])
                ->filter(fn ($a) => $a['years'] >= 1)
                ->sortBy(fn ($a) => (int) $a['employee']->date_hired->day)->take(6)->values();

            $data['activity'] = AuditLog::with('user')->latest()->take(8)->get();
        }

        return view('dashboard', $data);
    }

    /** @return array<int, array{name: string, desc: string, due: Carbon, days: int}> */
    private function statutoryDeadlines(): array
    {
        $today = now();

        $next = function (string $name, int $day, string $desc) use ($today) {
            $due = $today->copy()->startOfMonth()->addDays($day - 1);

            if ($due->isPast()) {
                $due = $due->copy()->addMonth();
            }

            return ['name' => $name, 'desc' => $desc, 'due' => $due, 'days' => (int) $today->diffInDays($due, false)];
        };

        return [
            $next('SSS R-3', 10, 'Contributions'),
            $next('PhilHealth RF-1', 10, 'Contributions'),
            $next('Pag-IBIG MCRF', 15, 'Contributions'),
            $next('BIR 1601-C', 10, 'Withholding tax'),
        ];
    }

    private function employeeDashboard(User $user): View
    {
        $employee = $user->employee;
        $today = now()->toDateString();

        $attendance = $employee?->attendances()->whereDate('work_date', $today)->first();
        $payslips = $employee ? $employee->payslips()->with('run.period')->latest()->take(5)->get() : collect();
        $leaveBalances = $employee ? $employee->leaveBalances()->where('year', now()->year)->with('leaveType')->get() : collect();

        return view('dashboard-employee', compact('employee', 'attendance', 'payslips', 'leaveBalances'));
    }
}
