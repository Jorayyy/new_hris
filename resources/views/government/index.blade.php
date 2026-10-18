@extends('layouts.app')

@section('title', "Government Tables")
@section('content')
    <p class="muted small">Edits apply to payroll runs processed after saving. Payslips already processed keep the amounts they were computed with.</p>

    <div class="card">
        <div class="card-head">
            <h2>SSS Contribution</h2>
            <span class="muted">Rebuild the whole schedule from the current rate</span>
        </div>
        <form method="POST" action="{{ route('government.sss.rebuild') }}" class="form-grid card-section" onsubmit="return confirm('Rebuild the SSS schedule from these parameters?')">
            @csrf @method('PUT')
            <div class="field"><label>Minimum MSC</label><input type="number" step="0.01" min="1" name="min_msc" value="5000" required></div>
            <div class="field"><label>Maximum MSC</label><input type="number" step="0.01" min="1" name="max_msc" value="35000" required></div>
            <div class="field"><label>Step (MSC)</label><input type="number" step="0.01" min="1" name="step" value="500" required></div>
            <div class="field"><label>Employee share (%)</label><input type="number" step="0.0001" min="0" max="100" name="employee_rate" value="5" required></div>
            <div class="field"><label>Employer share (%)</label><input type="number" step="0.0001" min="0" max="100" name="employer_rate" value="10" required></div>
            <div class="field"><label>EC share</label><input type="number" step="0.01" min="0" name="ec_share" value="10" required></div>
            <div class="field full"><button class="btn primary">Rebuild SSS schedule</button></div>
        </form>
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
        <div class="card-head">
            <h2>PhilHealth Contribution</h2>
            <span class="muted">{{ $philhealth->count() }} year(s)</span>
        </div>
        <form method="POST" action="{{ route('government.philhealth.store') }}" class="form-grid card-section">
            @csrf
            <div class="field"><label>Year</label><input type="number" min="2000" max="2100" name="year" value="{{ now()->year }}" required></div>
            <div class="field"><label>Minimum salary</label><input type="number" step="0.01" min="0" name="min_salary" value="10000" required></div>
            <div class="field"><label>Maximum salary</label><input type="number" step="0.01" min="0" name="max_salary" value="100000" required></div>
            <div class="field"><label>Employee rate (%)</label><input type="number" step="0.0001" min="0" max="100" name="employee_rate" value="2.5" required></div>
            <div class="field"><label>Employer rate (%)</label><input type="number" step="0.0001" min="0" max="100" name="employer_rate" value="2.5" required></div>
            <div class="field full"><button class="btn primary">Add year</button></div>
        </form>
        <div class="table-wrap">
            <table>
                <thead><tr><th class="num">Year</th><th class="num">Min salary</th><th class="num">Max salary</th><th class="num">Employee rate</th><th class="num">Employer rate</th><th></th></tr></thead>
                <tbody>
                @forelse($philhealth as $row)
                    <tr>
                        <td colspan="6" style="padding:0">
                            <form method="POST" action="{{ route('government.philhealth.update', $row) }}" class="row-form">
                                @csrf @method('PUT')
                                <input type="number" min="2000" max="2100" name="year" value="{{ $row->year }}" style="width:90px">
                                <input type="number" step="0.01" min="0" name="min_salary" value="{{ $row->min_salary }}" style="width:120px">
                                <input type="number" step="0.01" min="0" name="max_salary" value="{{ $row->max_salary }}" style="width:120px">
                                <label class="check"><input type="number" step="0.0001" min="0" max="100" name="employee_rate" value="{{ (float) $row->employee_rate * 100 }}" style="width:90px"> % EE</label>
                                <label class="check"><input type="number" step="0.0001" min="0" max="100" name="employer_rate" value="{{ (float) $row->employer_rate * 100 }}" style="width:90px"> % ER</label>
                                <button class="btn sm primary">Save</button>
                            </form>
                            <form method="POST" action="{{ route('government.philhealth.destroy', $row) }}" onsubmit="return confirm('Delete the PhilHealth table for {{ $row->year }}?')" style="padding:0 18px 13px">
                                @csrf @method('DELETE')
                                <button class="btn sm danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted">No PhilHealth table data.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-head">
            <h2>Pag-IBIG Contribution</h2>
            <span class="muted">Leave maximum salary blank for the top bracket</span>
        </div>
        <form method="POST" action="{{ route('government.pagibig.store') }}" class="form-grid card-section">
            @csrf
            <div class="field"><label>Minimum salary</label><input type="number" step="0.01" min="0" name="min_salary" value="0" required></div>
            <div class="field"><label>Maximum salary</label><input type="number" step="0.01" min="0" name="max_salary" placeholder="blank = no limit"></div>
            <div class="field"><label>Employee rate (%)</label><input type="number" step="0.0001" min="0" max="100" name="employee_rate" value="2" required></div>
            <div class="field"><label>Employer rate (%)</label><input type="number" step="0.0001" min="0" max="100" name="employer_rate" value="2" required></div>
            <div class="field"><label>Employee cap</label><input type="number" step="0.01" min="0" name="employee_cap" value="200" required></div>
            <div class="field"><label>Employer cap</label><input type="number" step="0.01" min="0" name="employer_cap" value="200" required></div>
            <div class="field full"><button class="btn primary">Add bracket</button></div>
        </form>
        <div class="table-wrap">
            <table>
                <thead><tr><th class="num">Salary range</th><th class="num">Employee rate</th><th class="num">Employer rate</th><th class="num">Employee cap</th><th class="num">Employer cap</th><th></th></tr></thead>
                <tbody>
                @forelse($pagibig as $row)
                    <tr>
                        <td colspan="6" style="padding:0">
                            <form method="POST" action="{{ route('government.pagibig.update', $row) }}" class="row-form">
                                @csrf @method('PUT')
                                <input type="number" step="0.01" min="0" name="min_salary" value="{{ $row->min_salary }}" style="width:110px">
                                <input type="number" step="0.01" min="0" name="max_salary" value="{{ $row->max_salary }}" placeholder="no limit" style="width:110px">
                                <label class="check"><input type="number" step="0.0001" min="0" max="100" name="employee_rate" value="{{ (float) $row->employee_rate * 100 }}" style="width:90px"> % EE</label>
                                <label class="check"><input type="number" step="0.0001" min="0" max="100" name="employer_rate" value="{{ (float) $row->employer_rate * 100 }}" style="width:90px"> % ER</label>
                                <input type="number" step="0.01" min="0" name="employee_cap" value="{{ $row->employee_cap }}" style="width:100px" title="Employee cap">
                                <input type="number" step="0.01" min="0" name="employer_cap" value="{{ $row->employer_cap }}" style="width:100px" title="Employer cap">
                                <button class="btn sm primary">Save</button>
                            </form>
                            <form method="POST" action="{{ route('government.pagibig.destroy', $row) }}" onsubmit="return confirm('Delete this Pag-IBIG bracket?')" style="padding:0 18px 13px">
                                @csrf @method('DELETE')
                                <button class="btn sm danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted">No Pag-IBIG table data.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-head">
            <h2>BIR Tax</h2>
            <form method="GET" class="btn-row">
                <select name="tax_year" onchange="this.form.submit()">
                    @foreach($taxYears as $year)
                        <option value="{{ $year }}" @selected((int) $taxYear === (int) $year)>{{ $year }}</option>
                    @endforeach
                </select>
            </form>
        </div>
        <form method="POST" action="{{ route('government.tax.store') }}" class="form-grid card-section">
            @csrf
            <div class="field"><label>Year</label><input type="number" min="2000" max="2100" name="year" value="{{ $taxYear }}" required></div>
            <div class="field"><label>Period</label>
                <select name="period">
                    <option value="weekly">Weekly</option>
                    <option value="semi_monthly">Semi-monthly</option>
                    <option value="monthly">Monthly</option>
                </select>
            </div>
            <div class="field"><label>Minimum taxable</label><input type="number" step="0.01" min="0" name="min_taxable" value="0" required></div>
            <div class="field"><label>Maximum taxable</label><input type="number" step="0.01" min="0" name="max_taxable" placeholder="blank = no limit"></div>
            <div class="field"><label>Base tax</label><input type="number" step="0.01" min="0" name="base_tax" value="0" required></div>
            <div class="field"><label>Rate (%)</label><input type="number" step="0.0001" min="0" max="100" name="rate" value="0" required></div>
            <div class="field"><label>Excess over</label><input type="number" step="0.01" min="0" name="excess_over" value="0" required></div>
            <div class="field full"><button class="btn primary">Add bracket</button></div>
        </form>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Period</th><th class="num">Taxable range</th><th class="num">Base tax</th><th class="num">Rate</th><th class="num">Excess over</th><th></th></tr></thead>
                <tbody>
                @forelse($tax as $row)
                    <tr>
                        <td colspan="6" style="padding:0">
                            <form method="POST" action="{{ route('government.tax.update', $row) }}" class="row-form">
                                @csrf @method('PUT')
                                <input type="number" min="2000" max="2100" name="year" value="{{ $row->year }}" style="width:90px">
                                <select name="period" style="width:130px">
                                    <option value="weekly" @selected($row->period === 'weekly')>Weekly</option>
                                    <option value="semi_monthly" @selected($row->period === 'semi_monthly')>Semi-monthly</option>
                                    <option value="monthly" @selected($row->period === 'monthly')>Monthly</option>
                                </select>
                                <input type="number" step="0.01" min="0" name="min_taxable" value="{{ $row->min_taxable }}" style="width:120px">
                                <input type="number" step="0.01" min="0" name="max_taxable" value="{{ $row->max_taxable }}" placeholder="no limit" style="width:120px">
                                <input type="number" step="0.01" min="0" name="base_tax" value="{{ $row->base_tax }}" style="width:110px" title="Base tax">
                                <label class="check"><input type="number" step="0.0001" min="0" max="100" name="rate" value="{{ (float) $row->rate * 100 }}" style="width:80px"> %</label>
                                <input type="number" step="0.01" min="0" name="excess_over" value="{{ $row->excess_over }}" style="width:120px" title="Excess over">
                                <button class="btn sm primary">Save</button>
                            </form>
                            <form method="POST" action="{{ route('government.tax.destroy', $row) }}" onsubmit="return confirm('Delete this tax bracket?')" style="padding:0 18px 13px">
                                @csrf @method('DELETE')
                                <button class="btn sm danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted">No tax table data for {{ $taxYear }}.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
