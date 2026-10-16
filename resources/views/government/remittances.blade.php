@extends('layouts.app')

@section('title', 'Government Remittances')
@section('content')
    <div class="grid cols-4" style="margin-bottom:20px">
        <div class="stat"><div class="k">SSS</div><div class="v">{{ peso($totals['sss']) }}</div></div>
        <div class="stat"><div class="k">PhilHealth</div><div class="v">{{ peso($totals['philhealth']) }}</div></div>
        <div class="stat"><div class="k">Pag-IBIG</div><div class="v">{{ peso($totals['pagibig']) }}</div></div>
        <div class="stat"><div class="k">Withholding tax</div><div class="v">{{ peso($totals['tax']) }}</div></div>
    </div>

    <div class="card">
        <div class="card-head"><h2>Remittances</h2></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Employee</th><th class="num">SSS</th><th class="num">PhilHealth</th><th class="num">Pag-IBIG</th><th class="num">Tax</th></tr></thead>
                <tbody>
                @forelse($payslips as $payslip)
                    <tr>
                        <td>{{ $payslip->employee->full_name }}</td>
                        <td class="num">{{ peso($payslip->sss_contribution) }}</td>
                        <td class="num">{{ peso($payslip->philhealth_contribution) }}</td>
                        <td class="num">{{ peso($payslip->pagibig_contribution) }}</td>
                        <td class="num">{{ peso($payslip->tax_withheld) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">No payslips found.</td></tr>
                @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <th>Total</th>
                        <th class="num">{{ peso($totals['sss']) }}</th>
                        <th class="num">{{ peso($totals['philhealth']) }}</th>
                        <th class="num">{{ peso($totals['pagibig']) }}</th>
                        <th class="num">{{ peso($totals['tax']) }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
@endsection
