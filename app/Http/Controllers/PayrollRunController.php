<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Services\PayrollService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class PayrollRunController extends Controller
{
    public function __construct(private readonly PayrollService $payroll) {}

    public function index(): View
    {
        $runs = PayrollRun::with(['period', 'branch'])->withCount('payslips')->latest()->paginate(15);

        return view('payroll.runs.index', ['runs' => $runs]);
    }

    public function create(): View
    {
        return view('payroll.runs.form', [
            'periods' => PayrollPeriod::orderByDesc('date_from')->get(),
            'branches' => Branch::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'payroll_period_id' => ['required', 'exists:payroll_periods,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
        ]);

        $run = PayrollRun::create($data + ['status' => 'draft']);

        return redirect()->route('payroll.runs.show', $run)->with('ok', 'Payroll run created. Review and process.');
    }

    public function show(PayrollRun $run): View
    {
        $run->load(['period', 'branch', 'payslips.employee']);

        return view('payroll.runs.show', compact('run'));
    }

    public function process(PayrollRun $run): RedirectResponse
    {
        $this->payroll->processRun($run);

        return redirect()->route('payroll.runs.show', $run)->with('ok', 'Payroll processed.');
    }

    public function destroy(PayrollRun $run): RedirectResponse
    {
        $this->payroll->deleteRun($run);

        return redirect()->route('payroll.runs.index')->with('ok', 'Payroll run deleted.');
    }

    public function export(PayrollRun $run): Response
    {
        $run->load(['payslips.employee']);

        $rows = [['Employee No', 'Name', 'Bank', 'Account No', 'Gross Pay', 'Total Deductions', 'Net Pay']];
        foreach ($run->payslips as $payslip) {
            $rows[] = [
                $payslip->employee?->employee_no,
                $payslip->employee?->full_name,
                $payslip->employee?->bank_name,
                $payslip->employee?->bank_account_no,
                number_format((float) $payslip->gross_pay, 2, '.', ''),
                number_format((float) $payslip->total_deductions, 2, '.', ''),
                number_format((float) $payslip->net_pay, 2, '.', ''),
            ];
        }

        $csv = '';
        foreach ($rows as $row) {
            $csv .= implode(',', array_map(fn ($value) => '"'.str_replace('"', '""', (string) $value).'"', $row))."\r\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="payroll-run-'.$run->id.'.csv"',
        ]);
    }

    public function thirteenthMonth(Request $request): View
    {
        $year = $request->integer('year') ?: (int) now()->year;
        $employees = Employee::where('is_active', true)->orderBy('last_name')->get()
            ->map(fn (Employee $e) => [
                'employee' => $e,
                'amount' => $this->payroll->thirteenthMonth($e, $year),
            ]);

        return view('payroll.thirteenth-month', [
            'year' => $year,
            'rows' => $employees,
            'total' => $employees->sum('amount'),
        ]);
    }
}
