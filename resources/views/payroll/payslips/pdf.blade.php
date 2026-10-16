<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payslip</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1b2436; }
        h1 { font-size: 16px; margin: 0 0 2px; }
        .muted { color: #6b7688; }
        .box { border: 1px solid #d5dbe6; border-radius: 6px; padding: 10px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        td, th { padding: 5px 6px; border-bottom: 1px solid #eef1f6; text-align: left; }
        th { background: #f4f6fb; font-size: 10px; text-transform: uppercase; }
        .num { text-align: right; }
        .cols { width: 100%; }
        .cols td { vertical-align: top; width: 50%; padding: 0 6px; }
        .total { background: #eaeeff; font-weight: bold; }
        .net { font-size: 15px; font-weight: bold; }
    </style>
</head>
<body>
    <h1>{{ config('app.name') }} — Payslip</h1>
    <div class="muted">
        {{ $payslip->employee?->full_name }} ({{ $payslip->employee?->employee_no }})<br>
        {{ $payslip->employee?->position?->title }} — {{ $payslip->employee?->branch?->name }}<br>
        Period: {{ $payslip->run?->period?->name }} ({{ $payslip->run?->period?->date_from?->format('M d') }} – {{ $payslip->run?->period?->date_to?->format('M d, Y') }})
    </div>
    <br>
    <table class="cols">
        <tr>
            <td>
                <div class="box">
                    <strong>Earnings</strong>
                    <table>
                        @foreach($payslip->items->where('type', 'earning') as $item)
                            <tr><td>{{ $item->label }}</td><td class="num">{{ peso($item->amount) }}</td></tr>
                        @endforeach
                        <tr class="total"><td>Gross pay</td><td class="num">{{ peso($payslip->gross_pay) }}</td></tr>
                    </table>
                </div>
            </td>
            <td>
                <div class="box">
                    <strong>Deductions</strong>
                    <table>
                        @foreach($payslip->items->where('type', 'deduction') as $item)
                            <tr><td>{{ $item->label }}</td><td class="num">{{ peso($item->amount) }}</td></tr>
                        @endforeach
                        <tr class="total"><td>Total deductions</td><td class="num">{{ peso($payslip->total_deductions) }}</td></tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>
    <div class="box" style="text-align:right">
        NET PAY: <span class="net">{{ peso($payslip->net_pay) }}</span>
    </div>
    <p class="muted" style="font-size:9px">
        Days worked: {{ hrs($payslip->days_worked, 0) }} · Hours: {{ hrs($payslip->hours_worked) }} ·
        Generated {{ now()->format('M d, Y h:i A') }}
    </p>
</body>
</html>
