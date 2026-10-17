@extends('layouts.app')

@section('title', 'My Pay')
@section('content')
    <div class="card">
        <div class="card-head">
            <h2>My pay</h2>
            <span class="muted">{{ $employee->position?->title ?? '—' }} · {{ $employee->branch?->name ?? '—' }}</span>
        </div>
        <div class="card-body">
            <dl class="kv">
                <dt>Basic salary</dt><dd>{{ peso($employee->basic_salary) }} / month</dd>
                <dt>Monthly allowance</dt><dd>{{ peso($employee->monthly_allowance) }}</dd>
                <dt>Daily rate</dt><dd>{{ peso($employee->dailyRate()) }}</dd>
                <dt>Hourly rate</dt><dd>{{ peso($employee->hourly_rate) }}</dd>
            </dl>
        </div>
    </div>

    <div class="card">
        <div class="card-head">
            <h2>13th month pay ({{ $thirteenth['year'] }})</h2>
            <span class="muted">Total basic salary earned this year ÷ 12</span>
        </div>
        <div class="card-body">
            <div class="grid cols-2">
                <dl class="kv">
                    <dt>Basic earned this year</dt><dd>{{ peso($thirteenth['ytd_basic']) }}</dd>
                    <dt>Pay periods covered</dt><dd>{{ $thirteenth['months_covered'] }} month(s)</dd>
                </dl>
                <dl class="kv">
                    <dt>Accrued so far</dt><dd style="font-weight:600">{{ peso($thirteenth['accrued']) }}</dd>
                    <dt>Projected for the year</dt><dd style="font-weight:600">{{ peso($thirteenth['projected']) }}</dd>
                </dl>
            </div>
            <p class="muted small">
                Tax-exempt up to {{ peso($thirteenth['tax_exempt_cap']) }}. Projected taxable excess: {{ peso($thirteenth['taxable_excess']) }}.
                The projection assumes your average basic pay so far continues; the amount released at year end is based on actual basic pay earned for the whole year.
            </p>
        </div>
    </div>

    <div class="card">
        <div class="card-head">
            <h2>My loans</h2>
            <span class="muted">Balance drops as payroll deducts each amortization</span>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Loan</th><th>Reference</th><th class="num">Principal</th><th class="num">Paid</th><th class="num">Balance</th><th class="num">Per month</th><th>Status</th></tr></thead>
                <tbody>
                @forelse($loans as $loan)
                    <tr>
                        <td style="font-weight:600">{{ $loan->type?->name }}</td>
                        <td class="muted small">{{ $loan->reference_no ?: '—' }}</td>
                        <td class="num">{{ peso($loan->principal) }}</td>
                        <td class="num">{{ peso((float) $loan->principal - (float) $loan->balance) }}</td>
                        <td class="num" style="font-weight:600">{{ peso($loan->balance) }}</td>
                        <td class="num">{{ peso($loan->monthly_amortization) }}</td>
                        <td><span class="badge {{ $loan->status === 'active' ? 'blue' : 'green' }}">{{ ucfirst($loan->status) }}</span></td>
                    </tr>
                    @if($loan->payments->isNotEmpty())
                        <tr>
                            <td colspan="7" class="muted small" style="padding-left:24px">
                                Payments:
                                @foreach($loan->payments->take(6) as $payment)
                                    {{ peso($payment->amount) }} on {{ $payment->paid_on?->format('M d, Y') ?? '—' }}@if(! $loop->last) · @endif
                                @endforeach
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr><td colspan="7" class="muted">No loans on record.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="grid cols-2">
        <div class="card">
            <div class="card-head"><h2>Allowances</h2></div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Name</th><th class="num">Amount</th><th>Taxable</th><th>Recurring</th></tr></thead>
                    <tbody>
                    @forelse($employee->allowances as $allowance)
                        <tr>
                            <td>{{ $allowance->name }}</td>
                            <td class="num">{{ peso($allowance->amount) }}</td>
                            <td><span class="badge {{ $allowance->is_taxable ? 'amber' : 'green' }}">{{ $allowance->is_taxable ? 'Taxable' : 'Non-taxable' }}</span></td>
                            <td class="muted small">{{ $allowance->is_recurring ? 'Every period' : 'One-time' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="muted">No allowances on record.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-head"><h2>Deductions</h2></div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Name</th><th class="num">Amount</th><th>Recurring</th></tr></thead>
                    <tbody>
                    @forelse($employee->deductions as $deduction)
                        <tr>
                            <td>{{ $deduction->name }}</td>
                            <td class="num">{{ peso($deduction->amount) }}</td>
                            <td class="muted small">{{ $deduction->is_recurring ? 'Every period' : 'One-time' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="muted">No recurring deductions on record.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
