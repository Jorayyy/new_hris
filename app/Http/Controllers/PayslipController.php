<?php

namespace App\Http\Controllers;

use App\Models\Payslip;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\View\View;

class PayslipController extends Controller
{
    public function show(Payslip $payslip): View
    {
        $payslip->load(['employee.branch', 'employee.department', 'run.period', 'items']);

        return view('payroll.payslips.show', compact('payslip'));
    }

    public function pdf(Payslip $payslip): Response
    {
        $payslip->load(['employee.branch', 'employee.department', 'run.period', 'items']);

        return Pdf::loadView('payroll.payslips.pdf', compact('payslip'))
            ->setPaper('a4')
            ->download('payslip-'.$payslip->employee->employee_no.'-'.$payslip->run->period->name.'.pdf');
    }
}
