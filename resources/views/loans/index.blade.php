@extends('layouts.app')

@section('title', 'Employee Loans')
@section('content')
    <div class="card">
        <div class="card-head">
            <h2>New Employee Loan</h2>
        </div>
        <form method="POST" action="{{ route('loans.store') }}" class="form-grid card-section">
            @csrf
            <div class="field"><label>Employee</label>
                <select name="employee_id" required>
                    <option value="">Select employee…</option>
                    @foreach($employees as $e)
                        <option value="{{ $e->id }}" @selected(old('employee_id') == $e->id)>{{ $e->full_name }} ({{ $e->employee_no }})</option>
                    @endforeach
                </select>
            </div>
            <div class="field"><label>Loan type</label>
                <select name="loan_type_id" required>
                    <option value="">Select type…</option>
                    @foreach($loanTypes as $type)
                        <option value="{{ $type->id }}" @selected(old('loan_type_id') == $type->id)>{{ $type->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field"><label>Principal</label><input type="number" step="0.01" min="1" name="principal" value="{{ old('principal') }}" required></div>
            <div class="field"><label>Monthly amortization</label><input type="number" step="0.01" min="1" name="monthly_amortization" value="{{ old('monthly_amortization') }}" required></div>
            <div class="field"><label>Reference no.</label><input name="reference_no" value="{{ old('reference_no') }}"></div>
            <div class="field"><label>Start date</label><input type="date" name="start_date" value="{{ old('start_date') }}"></div>
            <div class="field full"><label>Notes</label><input name="notes" value="{{ old('notes') }}"></div>
            <div class="field full"><button class="btn primary">Create loan</button></div>
        </form>
    </div>

    <div class="card">
        <div class="card-head">
            <h2>Loans</h2>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Employee</th><th>Type</th><th>Reference</th><th class="num">Principal</th><th class="num">Monthly</th><th class="num">Balance</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse($loans as $loan)
                    <tr>
                        <td style="font-weight:600">{{ $loan->employee?->full_name }}</td>
                        <td>{{ $loan->type?->name }}</td>
                        <td>{{ $loan->reference_no ?: '—' }}</td>
                        <td class="num">{{ number_format((float) $loan->principal, 2) }}</td>
                        <td class="num">{{ number_format((float) $loan->monthly_amortization, 2) }}</td>
                        <td class="num">{{ number_format((float) $loan->balance, 2) }}</td>
                        <td><span class="badge {{ $loan->status === 'completed' ? 'gray' : 'green' }}">{{ ucfirst($loan->status) }}</span></td>
                        <td class="btn-row">
                            <form method="POST" action="{{ route('loans.destroy', $loan) }}" onsubmit="return confirm('Delete this loan?')">
                                @csrf @method('DELETE')
                                <button class="btn sm danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="muted">No employee loans yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $loans->links() }}
    </div>
@endsection
