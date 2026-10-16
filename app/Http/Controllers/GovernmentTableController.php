<?php

namespace App\Http\Controllers;

use App\Models\PagibigContribution;
use App\Models\Payslip;
use App\Models\PhilhealthContribution;
use App\Models\SssContribution;
use App\Models\TaxTable;
use App\Services\PhContributionService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GovernmentTableController extends Controller
{
    public function __construct(private readonly PhContributionService $ph) {}

    public function index(): View
    {
        return view('government.index', [
            'sss' => SssContribution::orderBy('min_msc')->get(),
            'philhealth' => PhilhealthContribution::orderByDesc('year')->get(),
            'pagibig' => PagibigContribution::orderBy('min_salary')->get(),
            'tax' => TaxTable::where('year', now()->year)->orderBy('period')->orderBy('min_taxable')->get(),
        ]);
    }

    public function remittances(Request $request): View
    {
        $runId = $request->integer('run');
        $payslips = Payslip::query()
            ->with(['employee', 'run.period'])
            ->when($runId, fn ($q) => $q->where('payroll_run_id', $runId))
            ->when(! $runId, fn ($q) => $q->whereHas('run', fn ($r) => $r->where('status', 'processed'))->latest('payroll_run_id'))
            ->get();

        $employer = ['sss' => 0.0, 'philhealth' => 0.0, 'pagibig' => 0.0];
        foreach ($payslips as $payslip) {
            $shares = $this->ph->employeePeriodShares($payslip->employee, $payslip->run?->period?->frequency ?? 'semi_monthly');
            $employer['sss'] += $shares['sss_employer'];
            $employer['philhealth'] += $shares['philhealth_employer'];
            $employer['pagibig'] += $shares['pagibig_employer'];
        }

        $totals = [
            'sss' => $payslips->sum('sss_contribution'),
            'philhealth' => $payslips->sum('philhealth_contribution'),
            'pagibig' => $payslips->sum('pagibig_contribution'),
            'tax' => $payslips->sum('tax_withheld'),
            'sss_employer' => round($employer['sss'], 2),
            'philhealth_employer' => round($employer['philhealth'], 2),
            'pagibig_employer' => round($employer['pagibig'], 2),
        ];

        return view('government.remittances', compact('payslips', 'totals'));
    }
}
