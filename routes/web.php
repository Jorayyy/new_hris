<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceCorrectionController;
use App\Http\Controllers\AttendanceExceptionController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\BiometricDeviceController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\ClearanceItemController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DisciplinaryCaseController;
use App\Http\Controllers\DocumentTypeController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeDocumentController;
use App\Http\Controllers\EmployeeLoanController;
use App\Http\Controllers\FinalPayController;
use App\Http\Controllers\GovernmentTableController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\LeaveBalanceController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\LeaveTypeController;
use App\Http\Controllers\LoanTypeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OvertimeRequestController;
use App\Http\Controllers\PayrollPeriodController;
use App\Http\Controllers\PayrollRunController;
use App\Http\Controllers\PayslipController;
use App\Http\Controllers\PinController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QaCriterionController;
use App\Http\Controllers\QaEvaluationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RosterController;
use App\Http\Controllers\ScheduleChangeRequestController;
use App\Http\Controllers\SelfServiceController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\ShiftSwapController;
use App\Http\Controllers\TimeClockController;
use App\Http\Controllers\TimeClockIpController;
use App\Http\Controllers\TimekeepingController;
use App\Http\Controllers\UserController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::post('time-clock/punch', [TimeClockController::class, 'punch'])
    ->middleware('throttle:60,1')
    ->name('time-clock.punch');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);

    Route::get('/forgot-password', [ForgotPasswordController::class, 'show'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'send'])->name('password.email');
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'show'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'reset'])->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');

    Route::middleware('role:'.User::ROLE_EMPLOYEE)->group(function () {
        Route::get('me/dtr', [SelfServiceController::class, 'dtr'])->name('me.dtr');
        Route::get('me/roster', [SelfServiceController::class, 'roster'])->name('me.roster');
        Route::get('me/tk', [TimekeepingController::class, 'index'])->name('me.tk');
        Route::post('me/tk', [TimekeepingController::class, 'store'])->name('me.tk.store');
        Route::get('me/payslips', [SelfServiceController::class, 'payslips'])->name('me.payslips');
        Route::get('me/payslips/{payslip}', [SelfServiceController::class, 'payslip'])->name('me.payslips.show');
        Route::get('me/leave', [SelfServiceController::class, 'leave'])->name('me.leave');
        Route::post('me/leave', [SelfServiceController::class, 'storeLeave'])->name('me.leave.store');
        Route::get('me/swaps', [ShiftSwapController::class, 'mine'])->name('me.swaps');
        Route::post('me/swaps', [ShiftSwapController::class, 'store'])->name('me.swaps.store');
        Route::get('me/ot', [OvertimeRequestController::class, 'mine'])->name('me.ot');
        Route::post('me/ot', [OvertimeRequestController::class, 'store'])->name('me.ot.store');
        Route::get('me/schedule', [ScheduleChangeRequestController::class, 'mine'])->name('me.schedule');
        Route::post('me/schedule', [ScheduleChangeRequestController::class, 'store'])->name('me.schedule.store');
        Route::get('me/profile', [ProfileController::class, 'edit'])->name('me.profile');
        Route::put('me/profile', [ProfileController::class, 'update'])->name('me.profile.update');
        Route::put('me/password', [ProfileController::class, 'updatePassword'])->name('me.password');
        Route::get('me/cases', [SelfServiceController::class, 'cases'])->name('me.cases');
        Route::get('me/scorecards', [SelfServiceController::class, 'scorecards'])->name('me.scorecards');
        Route::get('me/pay', [SelfServiceController::class, 'pay'])->name('me.pay');
        Route::get('me/cases/{case}', [SelfServiceController::class, 'case'])->name('me.cases.show');
        Route::post('me/cases/{case}/respond', [SelfServiceController::class, 'respondCase'])->name('me.cases.respond');
    });

    Route::middleware('role:'.implode(',', User::HR_ROLES))->group(function () {
        Route::resource('branches', BranchController::class)->except('show');
        Route::resource('departments', DepartmentController::class)->except('show');
        Route::resource('positions', PositionController::class)->except('show');
        Route::get('employees/export', [EmployeeController::class, 'export'])->name('employees.export');
        Route::get('employees/import', [EmployeeController::class, 'importForm'])->name('employees.import.form');
        Route::post('employees/import', [EmployeeController::class, 'import'])->name('employees.import');
        Route::resource('employees', EmployeeController::class);
        Route::post('employees/{employee}/documents', [EmployeeDocumentController::class, 'store'])->name('employees.documents.store');
        Route::delete('documents/{document}', [EmployeeDocumentController::class, 'destroy'])->name('documents.destroy');
        Route::post('employees/{employee}/regularize', [EmployeeController::class, 'regularize'])->name('employees.regularize');
        Route::post('employees/{employee}/separate', [EmployeeController::class, 'separate'])->name('employees.separate');
        Route::put('clearance-items/{clearance_item}', [ClearanceItemController::class, 'update'])->name('clearance-items.update');
        Route::get('documents', [EmployeeDocumentController::class, 'index'])->name('documents.index');
        Route::resource('document-types', DocumentTypeController::class)->except('show');

        Route::get('pins', [PinController::class, 'index'])->name('pins.index');
        Route::put('pins/{employee}', [PinController::class, 'update'])->name('pins.update');
        Route::post('pins/{employee}/generate', [PinController::class, 'generate'])->name('pins.generate');
        Route::resource('announcements', AnnouncementController::class)->except('show');
        Route::resource('shifts', ShiftController::class)->except('show');
        Route::resource('holidays', HolidayController::class)->except('show');

        Route::get('roster', [RosterController::class, 'index'])->name('roster.index');
        Route::post('roster', [RosterController::class, 'store'])->name('roster.store');
        Route::post('roster/publish', [RosterController::class, 'publish'])->name('roster.publish');
        Route::delete('roster/{assignment}', [RosterController::class, 'destroy'])->name('roster.destroy');

        Route::get('attendance/corrections', [AttendanceCorrectionController::class, 'index'])->name('attendance.corrections.index');
        Route::post('attendance/corrections/{correction}/decide', [AttendanceCorrectionController::class, 'decide'])->name('attendance.corrections.decide');

        Route::get('leave-balances', [LeaveBalanceController::class, 'index'])->name('leave-balances.index');
        Route::post('leave-balances', [LeaveBalanceController::class, 'store'])->name('leave-balances.store');
        Route::put('leave-balances/{leaveBalance}', [LeaveBalanceController::class, 'update'])->name('leave-balances.update');
        Route::post('leave-balances/accrue', [LeaveBalanceController::class, 'accrue'])->name('leave-balances.accrue');
    });

    Route::middleware('role:'.implode(',', [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN, User::ROLE_HR, User::ROLE_MANAGER]))->group(function () {
        Route::get('attendance/import', [AttendanceController::class, 'importForm'])->name('attendance.import.form');
        Route::post('attendance/import', [AttendanceController::class, 'import'])->name('attendance.import');
        Route::get('attendance/export', [AttendanceController::class, 'export'])->name('attendance.export');
        Route::get('attendance/exceptions', [AttendanceExceptionController::class, 'index'])->name('attendance.exceptions.index');
        Route::resource('attendance', AttendanceController::class)->except('show');

        Route::get('leave-requests/export', [LeaveRequestController::class, 'export'])->name('leave-requests.export');
        Route::resource('leave-requests', LeaveRequestController::class)->only(['index', 'store', 'destroy']);
        Route::post('leave-requests/{leaveRequest}/decide', [LeaveRequestController::class, 'approve'])->name('leave-requests.decide');

        Route::get('swaps', [ShiftSwapController::class, 'index'])->name('swaps.index');
        Route::post('swaps/{swap}/decide', [ShiftSwapController::class, 'decide'])->name('swaps.decide');

        Route::get('ot', [OvertimeRequestController::class, 'index'])->name('ot.index');
        Route::post('ot/{overtime}/decide', [OvertimeRequestController::class, 'decide'])->name('ot.decide');

        Route::get('schedule-changes', [ScheduleChangeRequestController::class, 'index'])->name('schedule-changes.index');
        Route::post('schedule-changes/{change}/decide', [ScheduleChangeRequestController::class, 'decide'])->name('schedule-changes.decide');

        Route::get('cases', [DisciplinaryCaseController::class, 'index'])->name('cases.index');
        Route::get('cases/create', [DisciplinaryCaseController::class, 'create'])->name('cases.create');
        Route::post('cases', [DisciplinaryCaseController::class, 'store'])->name('cases.store');
        Route::get('cases/{case}', [DisciplinaryCaseController::class, 'show'])->name('cases.show');
        Route::post('cases/{case}/notes', [DisciplinaryCaseController::class, 'storeAction'])->name('cases.actions.store');
        Route::post('cases/{case}/decide', [DisciplinaryCaseController::class, 'decide'])->name('cases.decide');

        Route::get('qa', [QaEvaluationController::class, 'index'])->name('qa.index');
        Route::get('qa/create', [QaEvaluationController::class, 'create'])->name('qa.create');
        Route::post('qa', [QaEvaluationController::class, 'store'])->name('qa.store');
        Route::get('qa/criteria', [QaCriterionController::class, 'index'])->name('qa.criteria');
        Route::put('qa/settings', [QaCriterionController::class, 'updateSettings'])->name('qa.settings');
        Route::post('qa/criteria', [QaCriterionController::class, 'store'])->name('qa-criteria.store');
        Route::put('qa/criteria/{qa_criterion}', [QaCriterionController::class, 'update'])->name('qa-criteria.update');
        Route::delete('qa/criteria/{qa_criterion}', [QaCriterionController::class, 'destroy'])->name('qa-criteria.destroy');
        Route::get('qa/{qa_evaluation}/edit', [QaEvaluationController::class, 'edit'])->name('qa.edit');
        Route::put('qa/{qa_evaluation}', [QaEvaluationController::class, 'update'])->name('qa.update');
        Route::delete('qa/{qa_evaluation}', [QaEvaluationController::class, 'destroy'])->name('qa.destroy');
        Route::get('qa/{qa_evaluation}', [QaEvaluationController::class, 'show'])->name('qa.show');
    });

    Route::middleware('role:'.implode(',', [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN, User::ROLE_HR, User::ROLE_PAYROLL]))->group(function () {
        Route::resource('leave-types', LeaveTypeController::class)->except('show');

        Route::resource('loan-types', LoanTypeController::class)->except('show');
        Route::resource('loans', EmployeeLoanController::class)->only(['index', 'store', 'destroy']);
    });

    Route::middleware('role:'.implode(',', User::PAYROLL_ROLES))->group(function () {
        Route::resource('payroll/periods', PayrollPeriodController::class)->except('show')->names('payroll.periods');
        Route::resource('payroll/runs', PayrollRunController::class)->except(['edit', 'update'])->names('payroll.runs');
        Route::post('payroll/runs/{run}/process', [PayrollRunController::class, 'process'])->name('payroll.runs.process');
        Route::post('payroll/runs/{run}/approve', [PayrollRunController::class, 'approve'])->name('payroll.runs.approve');
        Route::post('payroll/runs/{run}/void', [PayrollRunController::class, 'void'])->name('payroll.runs.void');
        Route::get('payroll/runs/{run}/export', [PayrollRunController::class, 'export'])->name('payroll.runs.export');
        Route::get('payroll/runs/{run}/bank-file', [PayrollRunController::class, 'bankFile'])->name('payroll.runs.bank-file');
        Route::get('payroll/runs/{run}/payslips-zip', [PayrollRunController::class, 'payslipsZip'])->name('payroll.runs.payslips-zip');
        Route::get('payroll/thirteenth-month', [PayrollRunController::class, 'thirteenthMonth'])->name('payroll.thirteenth');
        Route::get('payroll/final-pay', [FinalPayController::class, 'index'])->name('payroll.final-pay');

        Route::get('payslips/{payslip}', [PayslipController::class, 'show'])->name('payslips.show');
        Route::get('payslips/{payslip}/pdf', [PayslipController::class, 'pdf'])->name('payslips.pdf');

        Route::get('government', [GovernmentTableController::class, 'index'])->name('government.index');
        Route::get('government/remittances', [GovernmentTableController::class, 'remittances'])->name('government.remittances');

        Route::get('reports/sss-r3', [ReportController::class, 'sssR3'])->name('reports.sss-r3');
        Route::get('reports/philhealth-rf1', [ReportController::class, 'philhealthRf1'])->name('reports.philhealth-rf1');
        Route::get('reports/pagibig-mcrf', [ReportController::class, 'pagibigMcrf'])->name('reports.pagibig-mcrf');
        Route::get('reports/bir-1601c', [ReportController::class, 'bir1601c'])->name('reports.bir-1601c');
        Route::get('reports/bir-2316', [ReportController::class, 'bir2316'])->name('reports.bir-2316');
    });

    Route::middleware('role:'.User::ROLE_SUPER_ADMIN.','.User::ROLE_ADMIN)->group(function () {
        Route::resource('users', UserController::class)->except('show');
        Route::get('audit-log', [AuditLogController::class, 'index'])->name('audit.index');

        Route::get('time-clock/access', [TimeClockIpController::class, 'index'])->name('time-clock.ips.index');
        Route::post('time-clock/access', [TimeClockIpController::class, 'store'])->name('time-clock.ips.store');
        Route::put('time-clock/access/{ip}', [TimeClockIpController::class, 'update'])->name('time-clock.ips.update');
        Route::delete('time-clock/access/{ip}', [TimeClockIpController::class, 'destroy'])->name('time-clock.ips.destroy');

        Route::get('biometric-devices', [BiometricDeviceController::class, 'index'])->name('biometric-devices.index');
        Route::post('biometric-devices', [BiometricDeviceController::class, 'store'])->name('biometric-devices.store');
        Route::post('biometric-devices/{device}/regenerate', [BiometricDeviceController::class, 'regenerate'])->name('biometric-devices.regenerate');
        Route::put('biometric-devices/{device}', [BiometricDeviceController::class, 'update'])->name('biometric-devices.update');
        Route::delete('biometric-devices/{device}', [BiometricDeviceController::class, 'destroy'])->name('biometric-devices.destroy');
    });
});
