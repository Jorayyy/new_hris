@extends('layouts.app')

@section('title', 'My Payslips')
@section('content')
    <div class="card">
        <div class="card-head"><h2>My Payslips</h2></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Period</th><th>Coverage</th><th class="num">Gross</th><th class="num">Deductions</th><th class="num">Net</th><th></th></tr></thead>
                <tbody>
                @forelse($payslips as $payslip)
                    <tr>
                        <td style="font-weight:600">{{ $payslip->run?->period?->name }}</td>
                        <td class="small">{{ $payslip->run?->period?->date_from?->format('M d') }} – {{ $payslip->run?->period?->date_to?->format('M d, Y') }}</td>
                        <td class="num">{{ peso($payslip->gross_pay) }}</td>
                        <td class="num">{{ peso($payslip->total_deductions) }}</td>
                        <td class="num" style="font-weight:600">{{ peso($payslip->net_pay) }}</td>
                        <td><a class="btn sm" href="{{ route('me.payslips.show', $payslip) }}">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted">No payslips yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $payslips->links() }}
    </div>
@endsection
