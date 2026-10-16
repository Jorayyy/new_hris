<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $today = now()->toDateString();

        if ($user->hasRole('employee')) {
            return $this->employeeDashboard($user);
        }

        $stats = [
            'employees' => Employee::where('is_active', true)->count(),
            'present_today' => Attendance::whereDate('work_date', $today)->where('status', Attendance::STATUS_PRESENT)->count(),
            'on_leave' => LeaveRequest::where('status', LeaveRequest::STATUS_APPROVED)->whereDate('date_from', '<=', $today)->whereDate('date_to', '>=', $today)->count(),
            'pending_leave' => LeaveRequest::where('status', LeaveRequest::STATUS_PENDING)->count(),
        ];

        $recentPayroll = PayrollRun::with('period')->latest()->take(5)->get();
        $openPeriods = PayrollPeriod::where('status', 'open')->orderBy('date_from')->take(5)->get();

        return view('dashboard', compact('stats', 'recentPayroll', 'openPeriods'));
    }

    private function employeeDashboard($user): View
    {
        $employee = $user->employee;
        $today = now()->toDateString();

        $attendance = $employee?->attendances()->whereDate('work_date', $today)->first();
        $payslips = $employee ? $employee->payslips()->with('run.period')->latest()->take(5)->get() : collect();
        $leaveBalances = $employee ? $employee->leaveBalances()->where('year', now()->year)->with('leaveType')->get() : collect();

        return view('dashboard-employee', compact('employee', 'attendance', 'payslips', 'leaveBalances'));
    }
}
