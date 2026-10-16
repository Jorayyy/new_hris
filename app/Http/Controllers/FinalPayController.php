<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Services\FinalPayService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinalPayController extends Controller
{
    public function index(Request $request): View
    {
        $employee = null;
        $result = null;

        if ($request->filled('employee_id')) {
            $request->validate([
                'employee_id' => ['required', 'exists:employees,id'],
                'as_of' => ['required', 'date'],
            ]);

            $employee = Employee::findOrFail($request->integer('employee_id'));
            $result = (new FinalPayService)->compute($employee, $request->input('as_of'));
        }

        return view('payroll.final-pay', [
            'employees' => Employee::orderBy('last_name')->get(),
            'employee' => $employee,
            'asOf' => $request->input('as_of', now()->toDateString()),
            'result' => $result,
        ]);
    }
}
