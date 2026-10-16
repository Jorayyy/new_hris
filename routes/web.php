<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeDocumentController;
use App\Http\Controllers\EmployeeLoanController;
use App\Http\Controllers\GovernmentTableController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\LeaveTypeController;
use App\Http\Controllers\LoanTypeController;
use App\Http\Controllers\PayrollPeriodController;
use App\Http\Controllers\PayrollRunController;
use App\Http\Controllers\PayslipController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\SelfServiceController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\UserController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('me/dtr', [SelfServiceController::class, 'dtr'])->name('me.dtr');
    Route::get('me/payslips', [SelfServiceController::class, 'payslips'])->name('me.payslips');
    Route::get('me/payslips/{payslip}', [SelfServiceController::class, 'payslip'])->name('me.payslips.show');
    Route::get('me/leave', [SelfServiceController::class, 'leave'])->name('me.leave');
    Route::post('me/leave', [SelfServiceController::class, 'storeLeave'])->name('me.leave.store');
    Route::get('me/loans', [SelfServiceController::class, 'loans'])->name('me.loans');

    $mgmt = User::MANAGEMENT_ROLES;

    Route::middleware('role:'.implode(',', User::HR_ROLES))->group(function () {
        Route::resource('branches', BranchController::class)->except('show');
        Route::resource('departments', DepartmentController::class)->except('show');
        Route::resource('positions', PositionController::class)->except('show');
        Route::resource('employees', EmployeeController::class);
        Route::post('employees/{employee}/documents', [EmployeeDocumentController::class, 'store'])->name('employees.documents.store');
        Route::delete('documents/{document}', [EmployeeDocumentController::class, 'destroy'])->name('documents.destroy');
        Route::resource('announcements', AnnouncementController::class)->except('show');
    });

    Route::middleware('role:'.implode(',', $mgmt))->group(function () {
        Route::resource('shifts', ShiftController::class)->except('show');
        Route::resource('holidays', HolidayController::class)->except('show');

        Route::get('attendance/import', [AttendanceController::class, 'importForm'])->name('attendance.import.form');
        Route::post('attendance/import', [AttendanceController::class, 'import'])->name('attendance.import');
        Route::resource('attendance', AttendanceController::class)->except('show');

        Route::resource('leave-requests', LeaveRequestController::class)->only(['index', 'store', 'destroy']);
        Route::post('leave-requests/{leaveRequest}/decide', [LeaveRequestController::class, 'approve'])->name('leave-requests.decide');

        Route::resource('loans', EmployeeLoanController::class);
        Route::post('loans/{loan}/payment', [EmployeeLoanController::class, 'payment'])->name('loans.payment');

        Route::get('government', [GovernmentTableController::class, 'index'])->name('government.index');
        Route::get('government/remittances', [GovernmentTableController::class, 'remittances'])->name('government.remittances');
    });

    Route::middleware('role:'.implode(',', User::PAYROLL_ROLES))->group(function () {
        Route::resource('leave-types', LeaveTypeController::class)->except('show');
        Route::resource('loan-types', LoanTypeController::class)->except('show');

        Route::resource('payroll/periods', PayrollPeriodController::class)->except('show')->names('payroll.periods');
        Route::resource('payroll/runs', PayrollRunController::class)->except(['edit', 'update'])->names('payroll.runs');
        Route::post('payroll/runs/{run}/process', [PayrollRunController::class, 'process'])->name('payroll.runs.process');
        Route::get('payroll/thirteenth-month', [PayrollRunController::class, 'thirteenthMonth'])->name('payroll.thirteenth');

        Route::get('payslips/{payslip}', [PayslipController::class, 'show'])->name('payslips.show');
        Route::get('payslips/{payslip}/pdf', [PayslipController::class, 'pdf'])->name('payslips.pdf');
    });

    Route::middleware('role:'.User::ROLE_SUPER_ADMIN.','.User::ROLE_ADMIN)->group(function () {
        Route::resource('users', UserController::class)->except('show');
    });
});
