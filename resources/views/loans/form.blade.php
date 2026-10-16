@extends('layouts.app')

@section('title', 'New loan')
@section('content')
    <div class="card" style="max-width:720px">
        <div class="card-head"><h2>Record a loan</h2></div>
        <div class="card-body">
            <form method="POST" action="{{ route('loans.store') }}">
                @csrf
                <div class="form-grid">
                    <div class="field"><label>Employee</label>
                        <select name="employee_id" required><option value="">Select employee</option>
                            @foreach($employees as $employee)<option value="{{ $employee->id }}" @selected(old('employee_id') == $employee->id)>{{ $employee->full_name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="field"><label>Loan type</label>
                        <select name="loan_type_id" required><option value="">Select type</option>
                            @foreach($loanTypes as $type)<option value="{{ $type->id }}" @selected(old('loan_type_id') == $type->id)>{{ $type->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="field"><label>Principal</label><input type="number" step="0.01" name="principal" value="{{ old('principal') }}" required></div>
                    <div class="field"><label>Terms (months)</label><input type="number" name="terms_months" value="{{ old('terms_months', 12) }}" min="1" max="120" required></div>
                    <div class="field"><label>Start date</label><input type="date" name="start_date" value="{{ old('start_date', now()->format('Y-m-d')) }}" required></div>
                </div>
                <p class="muted small">Monthly amortization is computed as principal ÷ terms. Amortization is deducted automatically in payroll.</p>
                <div class="btn-row">
                    <button class="btn primary">Save loan</button>
                    <a class="btn" href="{{ route('loans.index') }}">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
