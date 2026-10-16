@extends('layouts.app')

@section('title', $attendance->exists ? 'Edit attendance' : 'New attendance')
@section('content')
    <div class="card" style="max-width:760px">
        <div class="card-head"><h2>{{ $attendance->exists ? 'Edit attendance' : 'New attendance' }}</h2></div>
        <div class="card-body">
            <form method="POST" action="{{ $attendance->exists ? route('attendance.update', $attendance) : route('attendance.store') }}">
                @csrf
                @if($attendance->exists) @method('PUT') @endif

                <div class="form-grid">
                    <div class="field"><label>Employee</label>
                        <select name="employee_id" required>
                            <option value="">Select employee</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}" @selected(old('employee_id', $attendance->employee_id) == $employee->id)>{{ $employee->full_name }} ({{ $employee->employee_no }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field"><label>Branch</label>
                        <select name="branch_id"><option value="">—</option>
                            @foreach($branches as $branch)<option value="{{ $branch->id }}" @selected(old('branch_id', $attendance->branch_id) == $branch->id)>{{ $branch->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="field"><label>Work date</label><input type="date" name="work_date" value="{{ old('work_date', optional($attendance->work_date)->format('Y-m-d')) }}" required></div>
                    <div class="field"><label>Status</label>
                        <select name="status">
                            @foreach(['present','absent','leave','holiday'] as $status)
                                <option value="{{ $status }}" @selected(old('status', $attendance->status) === $status)>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="label" style="margin:6px 0 10px">Punches</div>
                <div class="form-grid">
                    @foreach([
                        'time_in' => 'Time in',
                        'break1_out' => 'Break 1 out',
                        'break1_in' => 'Break 1 in',
                        'lunch_out' => 'Lunch out',
                        'lunch_in' => 'Lunch in',
                        'break2_out' => 'Break 2 out',
                        'break2_in' => 'Break 2 in',
                        'time_out' => 'Time out',
                    ] as $field => $label)
                        <div class="field"><label>{{ $label }}</label>
                            <input type="time" name="{{ $field }}" value="{{ old($field, optional($attendance->{$field})->format('H:i')) }}">
                        </div>
                    @endforeach
                </div>

                <div class="field full"><label>Remarks</label><input type="text" name="remarks" value="{{ old('remarks', $attendance->remarks) }}"></div>

                <p class="muted small">Late, undertime, overtime and night differential are computed automatically from the employee's assigned shift. Leave a punch blank if it did not happen.</p>
                <div class="btn-row">
                    <button class="btn primary">Save &amp; compute</button>
                    <a class="btn" href="{{ route('attendance.index') }}">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
