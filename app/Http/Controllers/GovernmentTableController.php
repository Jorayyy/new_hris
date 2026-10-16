<?php

namespace App\Http\Controllers;

use App\Models\PagibigContribution;
use App\Models\Payslip;
use App\Models\PhilhealthContribution;
use App\Models\SssContribution;
use App\Models\TaxTable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GovernmentTableController extends Controller
{
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
            ->with('employee')
            ->when($runId, fn ($q) => $q->where('payroll_run_id', $runId))
            ->when(! $runId, fn ($q) => $q->whereHas('run', fn ($r) => $r->where('status', 'processed'))->latest('payroll_run_id'))
            ->get();

        $totals = [
            'sss' => $payslips->sum('sss_contribution'),
            'philhealth' => $payslips->sum('philhealth_contribution'),
            'pagibig' => $payslips->sum('pagibig_contribution'),
            'tax' => $payslips->sum('tax_withheld'),
        ];

        return view('government.remittances', compact('payslips', 'totals'));
    }
}
