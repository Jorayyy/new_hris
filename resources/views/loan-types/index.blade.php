@extends('layouts.app')

@section('title', 'Loan Types')
@section('content')
    <div class="card">
        <div class="card-head">
            <h2>Loan Types</h2>
            <a class="btn primary sm" href="{{ route('loan-types.create') }}">+ New loan type</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Name</th><th>Code</th><th class="num">Interest %</th><th class="num">Max amount</th><th class="num">Loans</th><th>Active</th><th></th></tr></thead>
                <tbody>
                @forelse($loanTypes as $loanType)
                    <tr>
                        <td style="font-weight:600">{{ $loanType->name }}</td>
                        <td>{{ $loanType->code }}</td>
                        <td class="num">{{ number_format((float) $loanType->interest_rate * 100, 2) }}%</td>
                        <td class="num">{{ peso($loanType->max_amount) }}</td>
                        <td class="num">{{ $loanType->loans_count }}</td>
                        <td><span class="badge {{ $loanType->is_active ? 'green' : 'gray' }}">{{ $loanType->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td class="btn-row">
                            <a class="btn sm" href="{{ route('loan-types.edit', $loanType) }}">Edit</a>
                            <form method="POST" action="{{ route('loan-types.destroy', $loanType) }}" onsubmit="return confirm('Delete this loan type?')">
                                @csrf @method('DELETE')
                                <button class="btn sm danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="muted">No loan types yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $loanTypes->links() }}
    </div>
@endsection
