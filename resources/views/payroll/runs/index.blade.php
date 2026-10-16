@extends('layouts.app')

@section('title', 'Payroll Runs')
@section('content')
    <div class="card">
        <div class="card-head">
            <h2>Payroll Runs</h2>
            <div class="btn-row">
                <a class="btn sm" href="{{ route('payroll.thirteenth') }}">13th month</a>
                <a class="btn primary sm" href="{{ route('payroll.runs.create') }}">+ New run</a>
            </div>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Period</th><th>Branch</th><th>Status</th><th class="num">Employees</th><th class="num">Gross</th><th class="num">Deductions</th><th class="num">Net</th><th></th></tr></thead>
                <tbody>
                @forelse($runs as $run)
                    <tr>
                        <td style="font-weight:600">{{ $run->period?->name }}</td>
                        <td>{{ $run->branch?->name ?? 'All branches' }}</td>
                        <td><span class="badge {{ $run->status === 'processed' ? 'green' : 'gray' }}">{{ ucfirst($run->status) }}</span></td>
                        <td class="num">{{ $run->employee_count ?: $run->payslips_count }}</td>
                        <td class="num">{{ peso($run->gross_total) }}</td>
                        <td class="num">{{ peso($run->deductions_total) }}</td>
                        <td class="num" style="font-weight:600">{{ peso($run->net_total) }}</td>
                        <td><a class="btn sm" href="{{ route('payroll.runs.show', $run) }}">Open</a></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="muted">No payroll runs yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $runs->links() }}
    </div>
@endsection
