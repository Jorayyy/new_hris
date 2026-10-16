@extends('layouts.app')

@section('title', 'Final Pay')
@section('content')
    <div class="card">
        <div class="card-head">
            <h2>Final Pay Computation</h2>
        </div>
        <form method="GET" action="{{ route('payroll.final-pay') }}" class="form-grid" style="padding:16px">
            <div class="field"><label>Employee</label>
                <select name="employee_id" required>
                    <option value="">Select employee…</option>
                    @foreach($employees as $e)
                        <option value="{{ $e->id }}" @selected(request('employee_id') == $e->id)>{{ $e->full_name }} ({{ $e->employee_no }})</option>
                    @endforeach
                </select>
            </div>
            <div class="field"><label>As of date</label><input type="date" name="as_of" value="{{ $asOf }}" required></div>
            <div class="field full"><button class="btn primary">Compute</button></div>
        </form>
    </div>

    @if($result)
        <div class="card">
            <div class="card-head">
                <h2>{{ $employee->full_name }} — as of {{ $result['as_of'] }}</h2>
                <span class="muted">Unpaid from {{ $result['period_start'] }} · {{ $result['present_days'] }} days</span>
            </div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Component</th><th class="num">Amount</th></tr></thead>
                    <tbody>
                        <tr><td>Basic pay (unpaid days)</td><td class="num">{{ number_format($result['basic_pay'], 2) }}</td></tr>
                        <tr><td>13th month (pro-rated)</td><td class="num">{{ number_format($result['thirteenth_month'], 2) }}</td></tr>
                        <tr><td>Leave conversion ({{ $result['leave_days'] }} days)</td><td class="num">{{ number_format($result['leave_conversion'], 2) }}</td></tr>
                        <tr><td>Less: loan balance</td><td class="num">({{ number_format($result['loan_balance'], 2) }})</td></tr>
                        <tr><td>Less: other deductions</td><td class="num">({{ number_format($result['other_deductions'], 2) }})</td></tr>
                        <tr style="font-weight:700"><td>Net final pay</td><td class="num">{{ number_format($result['net_pay'], 2) }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
