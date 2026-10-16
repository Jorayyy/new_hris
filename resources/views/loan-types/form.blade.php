@extends('layouts.app')

@section('title', $loanType->exists ? 'Edit loan type' : 'New loan type')
@section('content')
    <div class="card" style="max-width:680px">
        <div class="card-head"><h2>{{ $loanType->exists ? 'Edit loan type' : 'New loan type' }}</h2></div>
        <div class="card-body">
            <form method="POST" action="{{ $loanType->exists ? route('loan-types.update', $loanType) : route('loan-types.store') }}">
                @csrf
                @if($loanType->exists) @method('PUT') @endif
                <div class="form-grid">
                    <div class="field">
                        <label>Name</label>
                        <input type="text" name="name" value="{{ old('name', $loanType->name) }}" required>
                    </div>
                    <div class="field">
                        <label>Code</label>
                        <input type="text" name="code" value="{{ old('code', $loanType->code) }}" required>
                    </div>
                    <div class="field">
                        <label>Interest rate <span class="muted small">(decimal, e.g. 0.02 = 2%)</span></label>
                        <input type="number" name="interest_rate" step="0.0001" value="{{ old('interest_rate', $loanType->interest_rate ?? 0) }}">
                    </div>
                    <div class="field">
                        <label>Max amount</label>
                        <input type="number" name="max_amount" step="0.01" value="{{ old('max_amount', $loanType->max_amount ?? 0) }}">
                    </div>
                    <div class="field full">
                        <label style="display:flex;align-items:center;gap:8px;font-weight:500">
                            <input type="checkbox" name="is_active" value="1" style="width:auto" @checked(old('is_active', $loanType->is_active ?? true))> Active
                        </label>
                    </div>
                </div>
                <div class="btn-row">
                    <button class="btn primary">Save loan type</button>
                    <a class="btn" href="{{ route('loan-types.index') }}">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
