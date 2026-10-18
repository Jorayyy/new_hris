@extends('layouts.app')

@section('title', $period->exists ? 'Edit pay period' : 'New pay period')
@section('back', route('payroll.periods.index'))
@section('content')
    <div class="card" style="max-width:760px">
        <div class="card-head"><h2>{{ $period->exists ? 'Edit pay period' : 'New pay period' }}</h2></div>
        <div class="card-body">
            <form method="POST" action="{{ $period->exists ? route('payroll.periods.update', $period) : route('payroll.periods.store') }}">
                @csrf
                @if($period->exists) @method('PUT') @endif
                <div class="form-grid">
                    <div class="field"><label>Name</label><input type="text" name="name" value="{{ old('name', $period->name) }}" placeholder="e.g. 1st half Jan 2026" required></div>
                    <div class="field"><label>Branch</label>
                        <select name="branch_id"><option value="">All branches</option>
                            @foreach($branches as $branch)<option value="{{ $branch->id }}" @selected(old('branch_id', $period->branch_id) == $branch->id)>{{ $branch->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="field"><label>Date from</label><input type="date" name="date_from" value="{{ old('date_from', optional($period->date_from)->format('Y-m-d')) }}" required></div>
                    <div class="field"><label>Date to</label><input type="date" name="date_to" value="{{ old('date_to', optional($period->date_to)->format('Y-m-d')) }}" required></div>
                    <div class="field"><label>Pay date</label><input type="date" name="pay_date" value="{{ old('pay_date', optional($period->pay_date)->format('Y-m-d')) }}" required></div>
                    <div class="field"><label>Frequency</label>
                        <select name="frequency">
                            <option value="semi_monthly" @selected(old('frequency', $period->frequency) === 'semi_monthly')>Semi-monthly</option>
                            <option value="monthly" @selected(old('frequency', $period->frequency) === 'monthly')>Monthly</option>
                            <option value="weekly" @selected(old('frequency', $period->frequency) === 'weekly')>Weekly</option>
                        </select>
                    </div>
                    <div class="field"><label>Status</label>
                        <select name="status">
                            @foreach(['open','closed','processed'] as $status)
                                <option value="{{ $status }}" @selected(old('status', $period->status) === $status)>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="btn-row">
                    <button class="btn primary">Save period</button>
                    <a class="btn" href="{{ route('payroll.periods.index') }}">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
