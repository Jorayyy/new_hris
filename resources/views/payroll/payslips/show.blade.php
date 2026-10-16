@extends('layouts.app')

@section('title', 'Payslip')
@section('content')
    @php($readonly = $readonly ?? false)
    <div class="card">
        <div class="card-head">
            <div>
                <h2>{{ $payslip->employee?->full_name }}</h2>
                <div class="muted small">
                    {{ $payslip->employee?->employee_no }} · {{ $payslip->run?->period?->name }}
                    · {{ $payslip->run?->period?->date_from?->format('M d') }} – {{ $payslip->run?->period?->date_to?->format('M d, Y') }}
                </div>
            </div>
            <div class="btn-row">
                <span class="badge green">Net {{ peso($payslip->net_pay) }}</span>
                @unless($readonly)
                    <a class="btn sm" href="{{ route('payslips.pdf', $payslip) }}">Download PDF</a>
                @endunless
            </div>
        </div>
        <div class="card-body">
            <div class="grid cols-2">
                <div>
                    <h3 class="small muted" style="text-transform:uppercase">Earnings</h3>
                    <table>
                        <tbody>
                        @foreach($payslip->items->where('type', 'earning') as $item)
                            <tr><td>{{ $item->label }}</td><td class="num">{{ peso($item->amount) }}</td></tr>
                        @endforeach
                        <tr style="font-weight:700"><td>Gross pay</td><td class="num">{{ peso($payslip->gross_pay) }}</td></tr>
                        </tbody>
                    </table>
                </div>
                <div>
                    <h3 class="small muted" style="text-transform:uppercase">Deductions</h3>
                    <table>
                        <tbody>
                        @foreach($payslip->items->where('type', 'deduction') as $item)
                            <tr><td>{{ $item->label }}</td><td class="num">{{ peso($item->amount) }}</td></tr>
                        @endforeach
                        <tr style="font-weight:700"><td>Total deductions</td><td class="num">{{ peso($payslip->total_deductions) }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div style="margin-top:18px;padding:16px;background:var(--primary-soft);border-radius:8px;display:flex;justify-content:space-between;align-items:center">
                <strong>NET PAY</strong>
                <strong style="font-size:20px">{{ peso($payslip->net_pay) }}</strong>
            </div>
        </div>
    </div>
@endsection
