@extends('layouts.app')

@section('title', 'My Loans')
@section('content')
    <div class="card">
        <div class="card-head"><h2>My Loans</h2></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Type</th><th class="num">Principal</th><th class="num">Monthly</th><th class="num">Balance</th><th>Status</th></tr></thead>
                <tbody>
                @forelse($loans as $loan)
                    <tr>
                        <td style="font-weight:600">{{ $loan->loanType?->name }}</td>
                        <td class="num">{{ peso($loan->principal) }}</td>
                        <td class="num">{{ peso($loan->monthly_amortization) }}</td>
                        <td class="num">{{ peso($loan->balance) }}</td>
                        <td><span class="badge {{ $loan->status === 'active' ? 'blue' : ($loan->status === 'paid' ? 'green' : 'gray') }}">{{ ucfirst($loan->status) }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">No loans on file.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
