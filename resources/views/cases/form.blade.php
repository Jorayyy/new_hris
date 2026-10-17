@extends('layouts.app')

@section('title', 'File a Case')
@section('back', route('cases.index'))
@section('content')
    <div class="card" style="max-width:720px">
        <div class="card-head"><h2>File an employee relations case</h2></div>
        <div class="card-body">
            <form method="POST" action="{{ route('cases.store') }}">
                @csrf
                <div class="field">
                    <label>Employee</label>
                    <select name="employee_id" required>
                        <option value="">Select employee</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" @selected(old('employee_id') == $employee->id)>{{ $employee->full_name }} ({{ $employee->employee_no }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid cols-3">
                    <div class="field">
                        <label>Type</label>
                        <select name="type" required>
                            @foreach($types as $key => $label)
                                <option value="{{ $key }}" @selected(old('type', $case->type) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label>Severity</label>
                        <select name="severity" required>
                            @foreach($severities as $key => $label)
                                <option value="{{ $key }}" @selected(old('severity', $case->severity) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label>Incident date</label>
                        <input type="date" name="incident_date" value="{{ old('incident_date', $case->incident_date?->toDateString()) }}">
                    </div>
                </div>
                <div class="field">
                    <label>Subject</label>
                    <input type="text" name="subject" value="{{ old('subject', $case->subject) }}" maxlength="200" required>
                </div>
                <div class="field">
                    <label>Details</label>
                    <textarea name="details" rows="6">{{ old('details', $case->details) }}</textarea>
                </div>
                <div class="field" style="max-width:260px">
                    <label>Response due on <span class="muted small">(for an NTE)</span></label>
                    <input type="date" name="response_due_on" value="{{ old('response_due_on', $case->response_due_on?->toDateString()) }}">
                </div>
                <p class="muted small">A notice to explain (NTE) opens the case for the employee to respond. Other types are recorded with you as the filer.</p>
                <div class="btn-row">
                    <button class="btn primary">File case</button>
                    <a class="btn" href="{{ route('cases.index') }}">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
