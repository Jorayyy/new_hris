@extends('layouts.app')

@section('title', 'Loan details')
@section('content')
    <div class="card">
        <div class="card-head">
            <h2>{{ $loan->employee?->full_name }} — {{ $loan->loanType?->name }}</h2>
            <span class="badge {{ $loan->status === 'active' ? 'blue' : ($loan->status === 'paid' ? 'green' : 'gray') }}">{{ ucfirst($loan->status) }}</span>
        </div>
        <div class="card-body">
            <dl class="kv">
                <dt>Principal</dt><dd>{{ peso($loan->principal) }}</dd>
                <dt>Terms</dt><dd>{{ $loan->terms_months }} months</dd>
                <dt>Monthly amortization</dt><dd>{{ peso($loan->monthly_amortization) }}</dd>
                <dt>Outstanding balance</dt><dd style="font-weight:700">{{ peso($loan->balance) }}</dd>
                <dt>Start date</dt><dd>{{ $loan->start_date->format('M d, Y') }}</dd>
            </dl>
        </div>
    </div>

    <div class="grid cols-2">
        <div class="card">
            <div class="card-head"><h2>Record a payment</h2></div>
            <div class="card-body">
                <form method="POST" action="{{ route('loans.payment', $loan) }}">
                    @csrf
                    <div class="field"><label>Amount</label><input type="number" step="0.01" name="amount" value="{{ old('amount', $loan->monthly_amortization) }}" required></div>
                    <div class="field"><label>Paid on</label><input type="date" name="paid_at" value="{{ now()->format('Y-m-d') }}" required></div>
                    <button class="btn primary">Record payment</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-head"><h2>Payment history</h2></div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Date</th><th class="num">Amount</th></tr></thead>
                    <tbody>
                    @forelse($loan->payments as $payment)
                        <tr><td>{{ $payment->paid_at->format('M d, Y') }}</td><td class="num">{{ peso($payment->amount) }}</td></tr>
                    @empty
                        <tr><td colspan="2" class="muted">No payments yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
