<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Services\PhContributionService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    public function __construct(private readonly PhContributionService $ph) {}

    public function sssR3(Request $request): View|Response
    {
        [$run, $payslips] = $this->context($request);

        $rows = [];
        $employeeTotal = $employerTotal = $ecTotal = 0.0;
        foreach ($payslips as $index => $payslip) {
            $employee = $payslip->employee;
            $sss = $this->ph->sssMonthly((float) $employee->basic_salary);

            $employeeTotal += $sss['employee'];
            $employerTotal += $sss['employer'];
            $ecTotal += $sss['ec'];

            $rows[] = [
                $index + 1, $employee->sss_no, $employee->full_name,
                number_format((float) $employee->basic_salary, 2), number_format($sss['msc'], 2),
                number_format($sss['employee'], 2), number_format($sss['employer'], 2),
                number_format($sss['ec'], 2), number_format($sss['employee'] + $sss['employer'] + $sss['ec'], 2),
            ];
        }

        return $this->output($request, 'SSS R-3 Contribution Report', $this->subtitle($run),
            ['#', 'SSS No', 'Employee', 'Monthly Salary', 'MSC', 'Employee', 'Employer', 'EC', 'Total'],
            $rows,
            ['', '', 'TOTAL', '', '', number_format($employeeTotal, 2), number_format($employerTotal, 2), number_format($ecTotal, 2), number_format($employeeTotal + $employerTotal + $ecTotal, 2)]);
    }

    public function philhealthRf1(Request $request): View|Response
    {
        [$run, $payslips] = $this->context($request);

        $rows = [];
        $employeeTotal = $employerTotal = 0.0;
        foreach ($payslips as $index => $payslip) {
            $employee = $payslip->employee;
            $ph = $this->ph->philhealthMonthly((float) $employee->basic_salary);

            $employeeTotal += $ph['employee'];
            $employerTotal += $ph['employer'];

            $rows[] = [
                $index + 1, $employee->philhealth_no, $employee->full_name,
                number_format($ph['basis'], 2), number_format($ph['employee'], 2),
                number_format($ph['employer'], 2), number_format($ph['total'], 2),
            ];
        }

        return $this->output($request, 'PhilHealth RF-1 Remittance Report', $this->subtitle($run),
            ['#', 'PhilHealth No', 'Employee', 'Salary Basis', 'Employee', 'Employer', 'Total'],
            $rows,
            ['', '', 'TOTAL', '', number_format($employeeTotal, 2), number_format($employerTotal, 2), number_format($employeeTotal + $employerTotal, 2)]);
    }

    public function pagibigMcrf(Request $request): View|Response
    {
        [$run, $payslips] = $this->context($request);

        $rows = [];
        $employeeTotal = $employerTotal = 0.0;
        foreach ($payslips as $index => $payslip) {
            $employee = $payslip->employee;
            $pi = $this->ph->pagibigMonthly((float) $employee->basic_salary);

            $employeeTotal += $pi['employee'];
            $employerTotal += $pi['employer'];

            $rows[] = [
                $index + 1, $employee->pagibig_no, $employee->full_name,
                number_format((float) $employee->basic_salary, 2), number_format($pi['employee'], 2),
                number_format($pi['employer'], 2), number_format($pi['employee'] + $pi['employer'], 2),
            ];
        }

        return $this->output($request, 'Pag-IBIG MCRF', $this->subtitle($run),
            ['#', 'Pag-IBIG No', 'Employee', 'Monthly Comp', 'Employee', 'Employer', 'Total'],
            $rows,
            ['', '', 'TOTAL', '', number_format($employeeTotal, 2), number_format($employerTotal, 2), number_format($employeeTotal + $employerTotal, 2)]);
    }

    public function bir1601c(Request $request): View|Response
    {
        [$run, $payslips] = $this->context($request);

        $rows = [];
        $grossTotal = $taxTotal = 0.0;
        foreach ($payslips as $index => $payslip) {
            $employee = $payslip->employee;
            $statutory = (float) $payslip->sss_contribution + (float) $payslip->philhealth_contribution + (float) $payslip->pagibig_contribution;
            $taxable = max(0, (float) $payslip->gross_pay - $statutory);

            $grossTotal += $taxable;
            $taxTotal += (float) $payslip->tax_withheld;

            $rows[] = [
                $index + 1, $employee->tin, $employee->full_name,
                number_format((float) $payslip->gross_pay, 2), number_format($statutory, 2),
                number_format($taxable, 2), number_format((float) $payslip->tax_withheld, 2),
            ];
        }

        return $this->output($request, 'BIR 1601-C Monthly Withholding Tax', $this->subtitle($run),
            ['#', 'TIN', 'Employee', 'Gross', 'Statutory', 'Taxable', 'Tax Withheld'],
            $rows,
            ['', '', 'TOTAL', '', '', number_format($grossTotal, 2), number_format($taxTotal, 2)]);
    }

    public function bir2316(Request $request): View|Response
    {
        $year = $request->integer('year') ?: (int) now()->year;

        $rows = [];
        $grossTotal = $taxTotal = 0.0;
        foreach (Employee::where('is_active', true)->orderBy('last_name')->get() as $index => $employee) {
            $slips = Payslip::query()
                ->where('employee_id', $employee->id)
                ->whereHas('run.period', fn ($q) => $q->whereYear('date_to', $year))
                ->get();

            if ($slips->isEmpty()) {
                continue;
            }

            $gross = (float) $slips->sum('gross_pay');
            $sss = (float) $slips->sum('sss_contribution');
            $ph = (float) $slips->sum('philhealth_contribution');
            $pi = (float) $slips->sum('pagibig_contribution');
            $tax = (float) $slips->sum('tax_withheld');
            $taxable = max(0, $gross - $sss - $ph - $pi);

            $grossTotal += $gross;
            $taxTotal += $tax;

            $rows[] = [
                $index + 1, $employee->tin, $employee->full_name,
                number_format($gross, 2), number_format($sss, 2), number_format($ph, 2), number_format($pi, 2),
                number_format($taxable, 2), number_format($tax, 2),
            ];
        }

        return $this->output($request, 'BIR 2316 Certificate of Compensation', 'Year '.$year,
            ['#', 'TIN', 'Employee', 'Gross', 'SSS', 'PhilHealth', 'Pag-IBIG', 'Taxable', 'Tax Withheld'],
            $rows,
            ['', '', 'TOTAL', number_format($grossTotal, 2), '', '', '', '', number_format($taxTotal, 2)]);
    }

    /** @return array{0: ?PayrollRun, 1: Collection} */
    private function context(Request $request): array
    {
        $run = $request->filled('run')
            ? PayrollRun::with('period')->find($request->integer('run'))
            : PayrollRun::with('period')->where('status', 'processed')->latest()->first();

        $payslips = $run
            ? $run->payslips()->with('employee')->get()
            : collect();

        return [$run, $payslips];
    }

    private function subtitle(?PayrollRun $run): string
    {
        return $run ? 'Pay period: '.$run->period?->name : 'No processed payroll run found';
    }

    private function output(Request $request, string $title, string $subtitle, array $headers, array $rows, ?array $totals = null): View|Response
    {
        if ($request->boolean('csv')) {
            $csv = '';
            foreach (array_merge([$headers], $rows, $totals ? [$totals] : []) as $row) {
                $csv .= implode(',', array_map(fn ($value) => '"'.str_replace('"', '""', (string) $value).'"', $row))."\r\n";
            }

            return response($csv, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="'.str_replace(' ', '-', strtolower($title)).'.csv"',
            ]);
        }

        return view('reports.table', compact('title', 'subtitle', 'headers', 'rows', 'totals'));
    }
}
