@extends('layouts.app')

@section('title', 'Employee Loans')
@section('content')
    <div class="card">
        <div class="card-head">
            <h2>Employee Loans</h2>
            <div class="btn-row">
                <a class="btn sm" href="{{ route('loan-types.index') }}">Loan types</a>
                <a class="btn primary sm" href="{{ route('loans.create') }}">+ New loan</a>
            </div>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Employee</th><th>Type</th><th class="num">Principal</th><th class="num">Amortization</th><th class="num">Balance</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse($loans as $loan)
                    <tr>
                        <td style="font-weight:600">{{ $loan->employee?->full_name }}</td>
                        <td>{{ $loan->loanType?->name }}</td>
                        <td class="num">{{ peso($loan->principal) }}</td>
                        <td class="num">{{ peso($loan->monthly_amortization) }}</td>
                        <td class="num">{{ peso($loan->balance) }}</td>
                        <td><span class="badge {{ $loan->status === 'active' ? 'blue' : ($loan->status === 'paid' ? 'green' : 'gray') }}">{{ ucfirst($loan->status) }}</span></td>
                        <td><a class="btn sm" href="{{ route('loans.show', $loan) }}">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="muted">No loans recorded.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $loans->links() }}
    </div>
@endsection
