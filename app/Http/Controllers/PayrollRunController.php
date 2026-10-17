<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Services\PayrollService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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

        AuditLog::record('create', 'Created payroll run #'.$run->id, $run);

        return redirect()->route('payroll.runs.show', $run)->with('ok', 'Payroll run created. Review and process.');
    }

    public function show(PayrollRun $run): View
    {
        $run->load(['period', 'branch', 'payslips.employee']);

        return view('payroll.runs.show', compact('run'));
    }

    public function process(PayrollRun $run): RedirectResponse
    {
        abort_unless(in_array($run->status, [PayrollRun::STATUS_DRAFT, PayrollRun::STATUS_VOIDED], true), 403, 'Only draft or voided runs can be processed.');

        $this->payroll->processRun($run);
        AuditLog::record('process', 'Processed payroll run #'.$run->id.' ('.$run->period?->name.')', $run);

        return redirect()->route('payroll.runs.show', $run)->with('ok', 'Payroll processed. Awaiting checker approval.');
    }

    public function approve(PayrollRun $run): RedirectResponse
    {
        abort_unless($run->status === PayrollRun::STATUS_PROCESSED, 403, 'Only processed runs can be approved.');
        abort_if($run->processed_by === auth()->id(), 403, 'The approver must be different from the preparer.');

        $run->update(['status' => PayrollRun::STATUS_APPROVED, 'approved_by' => auth()->id(), 'approved_at' => now()]);
        AuditLog::record('approve', 'Approved payroll run #'.$run->id.' ('.$run->period?->name.')', $run);

        return redirect()->route('payroll.runs.show', $run)->with('ok', 'Payroll run approved.');
    }

    public function void(Request $request, PayrollRun $run): RedirectResponse
    {
        abort_unless(in_array($run->status, [PayrollRun::STATUS_PROCESSED, PayrollRun::STATUS_APPROVED], true), 403, 'Only processed or approved runs can be voided.');

        $data = $request->validate(['void_reason' => ['required', 'string', 'max:500']]);

        $this->payroll->voidRun($run, $data['void_reason'], auth()->id());
        AuditLog::record('void', 'Voided payroll run #'.$run->id.' ('.$run->period?->name.')', $run);

        return redirect()->route('payroll.runs.show', $run)->with('ok', 'Payroll run voided. It can be reprocessed.');
    }

    public function destroy(PayrollRun $run): RedirectResponse
    {
        AuditLog::record('delete', 'Deleted payroll run #'.$run->id, $run);
        $this->payroll->deleteRun($run);

        return redirect()->route('payroll.runs.index')->with('ok', 'Payroll run deleted.');
    }

    public function payslipsZip(PayrollRun $run): BinaryFileResponse
    {
        $run->load(['payslips.employee', 'period']);

        abort_if($run->payslips->isEmpty(), 404, 'No payslips to export.');

        $zipPath = tempnam(sys_get_temp_dir(), 'slips').'.zip';
        $zip = new \ZipArchive;
        $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        foreach ($run->payslips as $payslip) {
            $pdf = Pdf::loadView('payroll.payslips.pdf', compact('payslip'))->setPaper('a4')->output();
            $name = 'payslip-'.$payslip->employee->employee_no.'-'.preg_replace('/[^A-Za-z0-9]+/', '-', (string) $run->period?->name).'.pdf';
            $zip->addFromString($name, $pdf);
        }

        $zip->close();
        AuditLog::record('export', 'Downloaded payslips ZIP for run #'.$run->id, $run);

        return response()->download($zipPath, 'payslips-run-'.$run->id.'.zip')->deleteFileAfterSend(true);
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

        return $this->csv($rows, 'payroll-run-'.$run->id.'.csv');
    }

    /** Bank payout file (account name, number, bank, amount) for an approved run. */
    public function bankFile(PayrollRun $run): Response
    {
        abort_unless($run->status === PayrollRun::STATUS_APPROVED, 403, 'Only approved runs can be released for payout.');

        $run->load(['payslips.employee']);

        $rows = [['Account Name', 'Account Number', 'Bank', 'Amount']];
        foreach ($run->payslips as $payslip) {
            $employee = $payslip->employee;
            if (! $employee?->bank_account_no) {
                continue;
            }

            $rows[] = [
                $employee->full_name,
                $employee->bank_account_no,
                $employee->bank_name,
                number_format((float) $payslip->net_pay, 2, '.', ''),
            ];
        }

        AuditLog::record('export', 'Downloaded bank payout file for run #'.$run->id, $run);

        return $this->csv($rows, 'bank-payout-run-'.$run->id.'.csv');
    }

    /** @param  array<int, array<int, mixed>>  $rows */
    private function csv(array $rows, string $filename): Response
    {
        $csv = '';
        foreach ($rows as $row) {
            $csv .= implode(',', array_map(fn ($value) => '"'.str_replace('"', '""', (string) $value).'"', $row))."\r\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function thirteenthMonth(Request $request): View
    {
        $year = $request->integer('year') ?: (int) now()->year;
        $totals = $this->payroll->thirteenthMonthTotals($year);
        $employees = Employee::with('branch')->where('is_active', true)->orderBy('last_name')->get()
            ->map(fn (Employee $e) => [
                'employee' => $e,
                'amount' => $totals->get($e->id, 0.0),
            ]);

        return view('payroll.thirteenth-month', [
            'year' => $year,
            'rows' => $employees,
            'total' => $employees->sum('amount'),
        ]);
    }
}
