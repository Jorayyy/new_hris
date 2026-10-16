@extends('layouts.app')

@section('title', 'Government Remittances')
@section('content')
    <div class="grid cols-4" style="margin-bottom:20px">
        <div class="stat"><div class="k">SSS total</div><div class="v">{{ peso($totals['sss'] + $totals['sss_employer']) }}</div></div>
        <div class="stat"><div class="k">PhilHealth total</div><div class="v">{{ peso($totals['philhealth'] + $totals['philhealth_employer']) }}</div></div>
        <div class="stat"><div class="k">Pag-IBIG total</div><div class="v">{{ peso($totals['pagibig'] + $totals['pagibig_employer']) }}</div></div>
        <div class="stat"><div class="k">Withholding tax</div><div class="v">{{ peso($totals['tax']) }}</div></div>
    </div>

    <div class="card">
        <div class="card-head"><h2>Remittance summary</h2></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Agency</th><th class="num">Employee share</th><th class="num">Employer share</th><th class="num">Total to remit</th></tr></thead>
                <tbody>
                    <tr>
                        <td style="font-weight:600">SSS</td>
                        <td class="num">{{ peso($totals['sss']) }}</td>
                        <td class="num">{{ peso($totals['sss_employer']) }}</td>
                        <td class="num" style="font-weight:600">{{ peso($totals['sss'] + $totals['sss_employer']) }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight:600">PhilHealth</td>
                        <td class="num">{{ peso($totals['philhealth']) }}</td>
                        <td class="num">{{ peso($totals['philhealth_employer']) }}</td>
                        <td class="num" style="font-weight:600">{{ peso($totals['philhealth'] + $totals['philhealth_employer']) }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight:600">Pag-IBIG</td>
                        <td class="num">{{ peso($totals['pagibig']) }}</td>
                        <td class="num">{{ peso($totals['pagibig_employer']) }}</td>
                        <td class="num" style="font-weight:600">{{ peso($totals['pagibig'] + $totals['pagibig_employer']) }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight:600">Withholding tax</td>
                        <td class="num">{{ peso($totals['tax']) }}</td>
                        <td class="num">{{ peso(0) }}</td>
                        <td class="num" style="font-weight:600">{{ peso($totals['tax']) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><h2>Employee contributions</h2></div>
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
