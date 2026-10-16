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
                    <div class="field"><label>Time in</label><input type="datetime-local" name="time_in" value="{{ old('time_in', optional($attendance->time_in)->format('Y-m-d\TH:i')) }}"></div>
                    <div class="field"><label>Time out</label><input type="datetime-local" name="time_out" value="{{ old('time_out', optional($attendance->time_out)->format('Y-m-d\TH:i')) }}"></div>
                    <div class="field full"><label>Remarks</label><input type="text" name="remarks" value="{{ old('remarks', $attendance->remarks) }}"></div>
                </div>
                <p class="muted small">Late, undertime, overtime and night differential are computed automatically from the employee's assigned shift.</p>
                <div class="btn-row">
                    <button class="btn primary">Save &amp; compute</button>
                    <a class="btn" href="{{ route('attendance.index') }}">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
