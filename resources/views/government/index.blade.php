@extends('layouts.app')

@section('title', "Government Tables")
@section('content')
    <div class="card">
        <div class="card-head"><h2>SSS Contribution</h2></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th class="num">MSC range</th><th class="num">Employee share</th><th class="num">Employer share</th><th class="num">EC</th></tr></thead>
                <tbody>
                @forelse($sss as $row)
                    <tr>
                        <td class="num">{{ peso($row->min_msc) }} – {{ peso($row->max_msc) }}</td>
                        <td class="num">{{ peso($row->employee_share) }}</td>
                        <td class="num">{{ peso($row->employer_share) }}</td>
                        <td class="num">{{ peso($row->ec_share) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="muted">No SSS table data.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><h2>PhilHealth Contribution</h2></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th class="num">Year</th><th class="num">Min salary</th><th class="num">Max salary</th><th class="num">Employee rate</th><th class="num">Employer rate</th></tr></thead>
                <tbody>
                @forelse($philhealth as $row)
                    <tr>
                        <td class="num">{{ $row->year }}</td>
                        <td class="num">{{ peso($row->min_salary) }}</td>
                        <td class="num">{{ peso($row->max_salary) }}</td>
                        <td class="num">{{ number_format((float) $row->employee_rate * 100, 2) }}%</td>
                        <td class="num">{{ number_format((float) $row->employer_rate * 100, 2) }}%</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">No PhilHealth table data.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><h2>Pag-IBIG Contribution</h2></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th class="num">Salary range</th><th class="num">Employee rate</th><th class="num">Employer rate</th><th class="num">Employee cap</th><th class="num">Employer cap</th></tr></thead>
                <tbody>
                @forelse($pagibig as $row)
                    <tr>
                        <td class="num">{{ peso($row->min_salary) }} – {{ peso($row->max_salary) }}</td>
                        <td class="num">{{ number_format((float) $row->employee_rate * 100, 2) }}%</td>
                        <td class="num">{{ number_format((float) $row->employer_rate * 100, 2) }}%</td>
                        <td class="num">{{ peso($row->employee_cap) }}</td>
                        <td class="num">{{ peso($row->employer_cap) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">No Pag-IBIG table data.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><h2>BIR Tax ({{ now()->year }})</h2></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Period</th><th class="num">Taxable range</th><th class="num">Base tax</th><th class="num">Rate</th><th class="num">Excess over</th></tr></thead>
                <tbody>
                @forelse($tax as $row)
                    <tr>
                        <td>{{ ucfirst($row->period) }}</td>
                        <td class="num">{{ peso($row->min_taxable) }} – {{ peso($row->max_taxable) }}</td>
                        <td class="num">{{ peso($row->base_tax) }}</td>
                        <td class="num">{{ number_format((float) $row->rate * 100, 2) }}%</td>
                        <td class="num">{{ peso($row->excess_over) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">No tax table data for {{ now()->year }}.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
