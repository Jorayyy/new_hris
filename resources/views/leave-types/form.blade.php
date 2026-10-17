@extends('layouts.app')

@section('title', $leaveType->exists ? 'Edit leave type' : 'New leave type')
@section('content')
    <div class="card" style="max-width:680px">
        <div class="card-head"><h2>{{ $leaveType->exists ? 'Edit leave type' : 'New leave type' }}</h2></div>
        <div class="card-body">
            <form method="POST" action="{{ $leaveType->exists ? route('leave-types.update', $leaveType) : route('leave-types.store') }}">
                @csrf
                @if($leaveType->exists) @method('PUT') @endif
                <div class="form-grid">
                    <div class="field">
                        <label>Name</label>
                        <input type="text" name="name" value="{{ old('name', $leaveType->name) }}" required>
                    </div>
                    <div class="field">
                        <label>Code</label>
                        <input type="text" name="code" value="{{ old('code', $leaveType->code) }}" required>
                    </div>
                    <div class="field">
                        <label>Pay percentage</label>
                        <input type="number" name="pay_percentage" min="0" max="100" value="{{ old('pay_percentage', $leaveType->pay_percentage ?? 100) }}">
                    </div>
                    <div class="field">
                        <label>Max days per year</label>
                        <input type="number" name="max_days_per_year" min="0" value="{{ old('max_days_per_year', $leaveType->max_days_per_year ?? 0) }}">
                    </div>
                    <div class="field">
                        <label class="check">
                            <input type="checkbox" name="is_paid" value="1" @checked(old('is_paid', $leaveType->is_paid ?? true))> Paid
                        </label>
                    </div>
                    <div class="field">
                        <label class="check">
                            <input type="checkbox" name="is_cash_convertible" value="1" @checked(old('is_cash_convertible', $leaveType->is_cash_convertible))> Cash convertible
                        </label>
                    </div>
                    <div class="field">
                        <label class="check">
                            <input type="checkbox" name="requires_attachment" value="1" @checked(old('requires_attachment', $leaveType->requires_attachment))> Requires attachment
                        </label>
                    </div>
                    <div class="field">
                        <label class="check">
                            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $leaveType->is_active ?? true))> Active
                        </label>
                    </div>
                </div>
                <div class="btn-row">
                    <button class="btn primary">Save leave type</button>
                    <a class="btn" href="{{ route('leave-types.index') }}">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
