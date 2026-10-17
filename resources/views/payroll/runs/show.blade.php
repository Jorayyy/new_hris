@extends('layouts.app')

@section('title', 'Payroll run')
@section('back', route('payroll.runs.index'))
@section('content')
    <div class="card">
        <div class="card-head">
            <div>
                <h2>{{ $run->period?->name }}</h2>
                <div class="muted small">{{ $run->branch?->name ?? 'All branches' }} · {{ ucfirst($run->status) }}</div>
                @if($run->void_reason)
                    <div class="muted small">Voided: {{ $run->void_reason }}</div>
                @endif
            </div>
            <div class="btn-row">
                @if(in_array($run->status, ['draft', 'voided']))
                    <form method="POST" action="{{ route('payroll.runs.process', $run) }}">
                        @csrf
                        <button class="btn primary">{{ $run->status === 'voided' ? 'Re-run payroll' : 'Process payroll' }}</button>
                    </form>
                @endif
                @if($run->status === 'processed')
                    <span class="badge green">Processed {{ $run->processed_at?->format('M d, Y h:i A') }}</span>
                    @if($run->processed_by !== auth()->id())
                        <form method="POST" action="{{ route('payroll.runs.approve', $run) }}">@csrf<button class="btn primary">Approve</button></form>
                    @else
                        <span class="badge gray">Awaiting a second approver</span>
                    @endif
                @endif
                @if($run->status === 'approved')
                    <span class="badge green">Approved {{ $run->approved_at?->format('M d, Y h:i A') }}</span>
                    <a class="btn primary" href="{{ route('payroll.runs.bank-file', $run) }}">Bank payout file</a>
                @endif
                @if(in_array($run->status, ['processed', 'approved']))
                    <form method="POST" action="{{ route('payroll.runs.void', $run) }}" onsubmit="this.void_reason.value=prompt('Reason for voiding this run?'); return this.void_reason.value">
                        @csrf
                        <input type="hidden" name="void_reason">
                        <button class="btn danger">Void</button>
                    </form>
                @endif
                @if($run->payslips->count())
                    <a class="btn" href="{{ route('payroll.runs.export', $run) }}">Export CSV</a>
                    <a class="btn" href="{{ route('payroll.runs.payslips-zip', $run) }}">Payslips ZIP</a>
                @endif
                <form method="POST" action="{{ route('payroll.runs.destroy', $run) }}" onsubmit="return confirm('Delete this run and its payslips?')">@csrf @method('DELETE')<button class="btn danger">Delete</button></form>
            </div>
        </div>
        <div class="card-body">
            <div class="grid cols-4">
                <div class="stat"><div class="k">Employees</div><div class="v">{{ $run->employee_count ?: $run->payslips->count() }}</div></div>
                <div class="stat"><div class="k">Gross</div><div class="v" style="font-size:20px">{{ peso($run->gross_total) }}</div></div>
                <div class="stat"><div class="k">Deductions</div><div class="v" style="font-size:20px">{{ peso($run->deductions_total) }}</div></div>
                <div class="stat"><div class="k">Net payout</div><div class="v" style="font-size:20px">{{ peso($run->net_total) }}</div></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><h2>Payslips</h2></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Employee</th><th class="num">Days</th><th class="num">Gross</th><th class="num">SSS</th><th class="num">PhilHealth</th><th class="num">Pag-IBIG</th><th class="num">Tax</th><th class="num">Net</th><th></th></tr></thead>
                <tbody>
                @forelse($run->payslips as $payslip)
                    <tr>
                        <td style="font-weight:600">{{ $payslip->employee?->full_name }}</td>
                        <td class="num">{{ hrs($payslip->days_worked, 0) }}</td>
                        <td class="num">{{ peso($payslip->gross_pay) }}</td>
                        <td class="num">{{ peso($payslip->sss_contribution) }}</td>
                        <td class="num">{{ peso($payslip->philhealth_contribution) }}</td>
                        <td class="num">{{ peso($payslip->pagibig_contribution) }}</td>
                        <td class="num">{{ peso($payslip->tax_withheld) }}</td>
                        <td class="num" style="font-weight:600">{{ peso($payslip->net_pay) }}</td>
                        <td><a class="btn sm" href="{{ route('payslips.show', $payslip) }}">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="muted">Not yet processed. Click “Process payroll”.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
