<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\PagibigContribution;
use App\Models\Payslip;
use App\Models\PhilhealthContribution;
use App\Models\SssContribution;
use App\Models\TaxTable;
use App\Services\PhContributionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GovernmentTableController extends Controller
{
    public function __construct(private readonly PhContributionService $ph) {}

    public function index(Request $request): View
    {
        $taxYear = $request->integer('tax_year') ?: (int) now()->year;

        return view('government.index', [
            'sss' => SssContribution::orderBy('min_msc')->get(),
            'philhealth' => PhilhealthContribution::orderByDesc('year')->get(),
            'pagibig' => PagibigContribution::orderBy('min_salary')->get(),
            'tax' => TaxTable::where('year', $taxYear)->orderBy('period')->orderBy('min_taxable')->get(),
            'taxYear' => $taxYear,
            'taxYears' => TaxTable::query()->distinct()->orderByDesc('year')->pluck('year')
                ->push((int) now()->year)->unique()->sortDesc()->values(),
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

    public function rebuildSss(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'min_msc' => ['required', 'numeric', 'min:1'],
            'max_msc' => ['required', 'numeric', 'gt:min_msc'],
            'step' => ['required', 'numeric', 'min:1'],
            'employee_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'employer_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'ec_share' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $msc = (float) $data['min_msc'];
        $ceiling = (float) $data['max_msc'];
        $step = (float) $data['step'];
        $rows = [];

        while ($msc <= $ceiling && count($rows) < 500) {
            $rows[] = [
                'min_msc' => $msc,
                'max_msc' => $msc >= $ceiling ? 999999 : $msc + $step - 0.01,
                'employee_share' => round($msc * (float) $data['employee_rate'] / 100, 2),
                'employer_share' => round($msc * (float) $data['employer_rate'] / 100, 2),
                'ec_share' => $data['ec_share'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $msc += $step;
        }

        DB::transaction(function () use ($rows) {
            SssContribution::query()->delete();
            SssContribution::insert($rows);
        });

        AuditLog::record('update', 'Rebuilt SSS schedule: '.count($rows).' brackets, '.$data['employee_rate'].'% employee / '.$data['employer_rate'].'% employer shares');

        return back()->with('ok', 'SSS schedule rebuilt: '.count($rows).' brackets.');
    }

    public function storePhilhealth(Request $request): RedirectResponse
    {
        $contribution = PhilhealthContribution::create($this->philhealthData($request));
        AuditLog::record('create', 'Added PhilHealth table for '.$contribution->year, $contribution);

        return back()->with('ok', 'PhilHealth table added.');
    }

    public function updatePhilhealth(Request $request, PhilhealthContribution $philhealth): RedirectResponse
    {
        $philhealth->update($this->philhealthData($request));
        AuditLog::record('update', 'Updated PhilHealth table for '.$philhealth->year, $philhealth);

        return back()->with('ok', 'PhilHealth table updated.');
    }

    public function destroyPhilhealth(PhilhealthContribution $philhealth): RedirectResponse
    {
        AuditLog::record('delete', 'Deleted PhilHealth table for '.$philhealth->year, $philhealth);
        $philhealth->delete();

        return back()->with('ok', 'PhilHealth table deleted.');
    }

    public function storePagibig(Request $request): RedirectResponse
    {
        $contribution = PagibigContribution::create($this->pagibigData($request));
        AuditLog::record('create', 'Added Pag-IBIG bracket', $contribution);

        return back()->with('ok', 'Pag-IBIG bracket added.');
    }

    public function updatePagibig(Request $request, PagibigContribution $pagibig): RedirectResponse
    {
        $pagibig->update($this->pagibigData($request));
        AuditLog::record('update', 'Updated Pag-IBIG bracket', $pagibig);

        return back()->with('ok', 'Pag-IBIG bracket updated.');
    }

    public function destroyPagibig(PagibigContribution $pagibig): RedirectResponse
    {
        AuditLog::record('delete', 'Deleted Pag-IBIG bracket', $pagibig);
        $pagibig->delete();

        return back()->with('ok', 'Pag-IBIG bracket deleted.');
    }

    public function storeTax(Request $request): RedirectResponse
    {
        $row = TaxTable::create($this->taxData($request));
        AuditLog::record('create', 'Added BIR tax bracket ('.$row->year.' '.$row->period.')', $row);

        return back()->with('ok', 'Tax bracket added.');
    }

    public function updateTax(Request $request, TaxTable $tax): RedirectResponse
    {
        $tax->update($this->taxData($request));
        AuditLog::record('update', 'Updated BIR tax bracket ('.$tax->year.' '.$tax->period.')', $tax);

        return back()->with('ok', 'Tax bracket updated.');
    }

    public function destroyTax(TaxTable $tax): RedirectResponse
    {
        AuditLog::record('delete', 'Deleted BIR tax bracket ('.$tax->year.' '.$tax->period.')', $tax);
        $tax->delete();

        return back()->with('ok', 'Tax bracket deleted.');
    }

    private function philhealthData(Request $request): array
    {
        $data = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'min_salary' => ['required', 'numeric', 'min:0'],
            'max_salary' => ['required', 'numeric', 'gt:min_salary'],
            'employee_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'employer_rate' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        return [
            'year' => $data['year'],
            'min_salary' => $data['min_salary'],
            'max_salary' => $data['max_salary'],
            'employee_rate' => $this->rate($data['employee_rate']),
            'employer_rate' => $this->rate($data['employer_rate']),
        ];
    }

    private function pagibigData(Request $request): array
    {
        $data = $request->validate([
            'min_salary' => ['required', 'numeric', 'min:0'],
            'max_salary' => ['nullable', 'numeric', 'gt:min_salary'],
            'employee_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'employer_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'employee_cap' => ['required', 'numeric', 'min:0'],
            'employer_cap' => ['required', 'numeric', 'min:0'],
        ]);

        return [
            'min_salary' => $data['min_salary'],
            'max_salary' => $data['max_salary'] ?? null,
            'employee_rate' => $this->rate($data['employee_rate']),
            'employer_rate' => $this->rate($data['employer_rate']),
            'employee_cap' => $data['employee_cap'],
            'employer_cap' => $data['employer_cap'],
        ];
    }

    private function taxData(Request $request): array
    {
        $data = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'period' => ['required', Rule::in(['semi_monthly', 'monthly'])],
            'min_taxable' => ['required', 'numeric', 'min:0'],
            'max_taxable' => ['nullable', 'numeric', 'gt:min_taxable'],
            'base_tax' => ['required', 'numeric', 'min:0'],
            'rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'excess_over' => ['required', 'numeric', 'min:0'],
        ]);

        return [
            'year' => $data['year'],
            'period' => $data['period'],
            'min_taxable' => $data['min_taxable'],
            'max_taxable' => $data['max_taxable'] ?? null,
            'base_tax' => $data['base_tax'],
            'rate' => $this->rate($data['rate']),
            'excess_over' => $data['excess_over'],
        ];
    }

    private function rate(mixed $percent): float
    {
        return round((float) $percent / 100, 4);
    }
}
