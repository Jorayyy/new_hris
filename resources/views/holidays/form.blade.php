@extends('layouts.app')

@section('title', $holiday->exists ? 'Edit holiday' : 'New holiday')
@section('content')
    <div class="card" style="max-width:680px">
        <div class="card-head"><h2>{{ $holiday->exists ? 'Edit holiday' : 'New holiday' }}</h2></div>
        <div class="card-body">
            <form method="POST" action="{{ $holiday->exists ? route('holidays.update', $holiday) : route('holidays.store') }}">
                @csrf
                @if($holiday->exists) @method('PUT') @endif
                <div class="form-grid">
                    <div class="field">
                        <label>Branch</label>
                        <select name="branch_id">
                            <option value="">All branches</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" @selected(old('branch_id', $holiday->branch_id) == $branch->id)>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label>Date</label>
                        <input type="date" name="date" value="{{ old('date', $holiday->date?->format('Y-m-d')) }}" required>
                    </div>
                    <div class="field">
                        <label>Name</label>
                        <input type="text" name="name" value="{{ old('name', $holiday->name) }}" required>
                    </div>
                    <div class="field">
                        <label>Type</label>
                        <select name="type">
                            <option value="regular" @selected(old('type', $holiday->type) === 'regular')>Regular</option>
                            <option value="special" @selected(old('type', $holiday->type) === 'special')>Special</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Rate multiplier</label>
                        <input type="number" name="rate_multiplier" step="0.01" value="{{ old('rate_multiplier', $holiday->rate_multiplier ?? 1) }}" required>
                    </div>
                </div>
                <div class="btn-row">
                    <button class="btn primary">Save holiday</button>
                    <a class="btn" href="{{ route('holidays.index') }}">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
