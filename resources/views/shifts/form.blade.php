@extends('layouts.app')

@section('title', $shift->exists ? 'Edit shift' : 'New shift')
@section('content')
    <div class="card" style="max-width:680px">
        <div class="card-head"><h2>{{ $shift->exists ? 'Edit shift' : 'New shift' }}</h2></div>
        <div class="card-body">
            <form method="POST" action="{{ $shift->exists ? route('shifts.update', $shift) : route('shifts.store') }}">
                @csrf
                @if($shift->exists) @method('PUT') @endif
                <div class="form-grid">
                    <div class="field full">
                        <label>Name</label>
                        <input type="text" name="name" value="{{ old('name', $shift->name) }}" required>
                    </div>
                    <div class="field">
                        <label>Time in</label>
                        <input type="time" name="time_in" value="{{ old('time_in', $shift->time_in) }}" required>
                    </div>
                    <div class="field">
                        <label>Time out</label>
                        <input type="time" name="time_out" value="{{ old('time_out', $shift->time_out) }}" required>
                    </div>
                    <div class="field">
                        <label>Break (minutes)</label>
                        <input type="number" name="break_minutes" value="{{ old('break_minutes', $shift->break_minutes ?? 0) }}" min="0">
                    </div>
                    <div class="field">
                        <label>Grace (minutes)</label>
                        <input type="number" name="grace_minutes" value="{{ old('grace_minutes', $shift->grace_minutes ?? 0) }}" min="0">
                    </div>
                    <div class="field">
                        <label>Required hours</label>
                        <input type="number" name="required_hours" step="0.01" value="{{ old('required_hours', $shift->required_hours ?? 8) }}" min="0">
                    </div>
                    <div class="field">
                        <label class="check">
                            <input type="checkbox" name="is_night_diff" value="1" @checked(old('is_night_diff', $shift->is_night_diff))> Night differential
                        </label>
                    </div>
                    <div class="field full">
                        <label class="check">
                            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $shift->is_active ?? true))> Active
                        </label>
                    </div>
                </div>
                <div class="btn-row">
                    <button class="btn primary">Save shift</button>
                    <a class="btn" href="{{ route('shifts.index') }}">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
