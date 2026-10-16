@extends('layouts.app')

@section('title', 'Pay Periods')
@section('content')
    <div class="card">
        <div class="card-head">
            <h2>Pay Periods</h2>
            <a class="btn primary sm" href="{{ route('payroll.periods.create') }}">+ New period</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Name</th><th>Branch</th><th>Coverage</th><th>Pay date</th><th>Frequency</th><th>Status</th><th class="num">Runs</th><th></th></tr></thead>
                <tbody>
                @forelse($periods as $period)
                    <tr>
                        <td style="font-weight:600">{{ $period->name }}</td>
                        <td>{{ $period->branch?->name ?? 'All' }}</td>
                        <td class="small">{{ $period->date_from->format('M d') }} – {{ $period->date_to->format('M d, Y') }}</td>
                        <td>{{ $period->pay_date->format('M d, Y') }}</td>
                        <td>{{ ucfirst(str_replace('_', '-', $period->frequency)) }}</td>
                        <td><span class="badge {{ $period->status === 'open' ? 'green' : 'gray' }}">{{ ucfirst($period->status) }}</span></td>
                        <td class="num">{{ $period->runs_count }}</td>
                        <td class="btn-row">
                            <a class="btn sm" href="{{ route('payroll.periods.edit', $period) }}">Edit</a>
                            <form method="POST" action="{{ route('payroll.periods.destroy', $period) }}" onsubmit="return confirm('Delete this period?')">@csrf @method('DELETE')<button class="btn sm danger">Del</button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="muted">No pay periods yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $periods->links() }}
    </div>
@endsection
