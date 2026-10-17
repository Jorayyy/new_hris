@extends('layouts.app')

@section('title', $applicant->full_name)
@section('content')
    @php $canManage = auth()->user()->hasRole(\App\Models\User::HR_ROLES); @endphp

    <div class="card">
        <div class="card-head">
            <h2>{{ $applicant->full_name }}</h2>
            <div class="btn-row">
                <span class="badge {{ $applicant->stageTone() }}">{{ $applicant->stageLabel() }}</span>
                @if($canManage && ! $applicant->employee_id)
                    <form method="POST" action="{{ route('applicants.destroy', $applicant) }}" onsubmit="return confirm('Remove this applicant?')">
                        @csrf @method('DELETE')
                        <button class="btn sm danger">Delete</button>
                    </form>
                @endif
            </div>
        </div>
        <div class="card-body">
            <dl class="kv">
                <dt>Vacancy</dt><dd><a href="{{ route('vacancies.show', $vacancy) }}">{{ $vacancy->title }}</a></dd>
                <dt>Branch</dt><dd>{{ $vacancy->branch?->name ?? '—' }}</dd>
                <dt>Email</dt><dd>{{ $applicant->email ?: '—' }}</dd>
                <dt>Contact no.</dt><dd>{{ $applicant->contact_no ?: '—' }}</dd>
                <dt>Source</dt><dd>{{ $applicant->source ?: '—' }}</dd>
                <dt>Applied on</dt><dd>{{ $applicant->applied_on?->format('M d, Y') ?? '—' }}</dd>
                <dt>Notes</dt><dd>{{ $applicant->notes ?: '—' }}</dd>
                @if($applicant->employee)
                    <dt>Employee</dt>
                    <dd><a href="{{ route('employees.show', $applicant->employee) }}">{{ $applicant->employee->employee_no }} — {{ $applicant->employee->full_name }}</a></dd>
                @endif
            </dl>
        </div>
    </div>

    @if($canManage)
        <div class="grid cols-2">
            <div class="card">
                <div class="card-head"><h2>Move stage</h2></div>
                <form method="POST" action="{{ route('applicants.update', $applicant) }}" class="form-grid" style="padding:16px">
                    @csrf @method('PUT')
                    <div class="field full"><label>Stage</label>
                        <select name="stage">
                            @foreach(\App\Models\Applicant::STAGES as $value => $label)
                                <option value="{{ $value }}" @selected($applicant->stage === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field full"><label>Note (optional, added to history)</label><textarea name="note" rows="3"></textarea></div>
                    <div class="field full"><button class="btn primary">Save</button></div>
                </form>
            </div>

            @unless($applicant->employee_id)
                <div class="card">
                    <div class="card-head"><h2>Hire applicant</h2><span class="muted">Creates the employee record</span></div>
                    <form method="POST" action="{{ route('applicants.hire', $applicant) }}" class="form-grid" style="padding:16px">
                        @csrf
                        <div class="field"><label>Employee no.</label><input name="employee_no" value="{{ old('employee_no') }}" required></div>
                        <div class="field"><label>Basic salary</label><input type="number" step="0.01" min="0" name="basic_salary" value="{{ old('basic_salary') }}" required></div>
                        <div class="field"><label>Branch</label>
                            <select name="branch_id">
                                <option value="">—</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected((int) old('branch_id', $vacancy->branch_id) === $branch->id)>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field"><label>Department</label>
                            <select name="department_id">
                                <option value="">—</option>
                                @foreach($departments as $department)
                                    <option value="{{ $department->id }}" @selected((int) old('department_id', $vacancy->department_id) === $department->id)>{{ $department->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field"><label>Position</label>
                            <select name="position_id">
                                <option value="">—</option>
                                @foreach($positions as $position)
                                    <option value="{{ $position->id }}" @selected((int) old('position_id', $vacancy->position_id) === $position->id)>{{ $position->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field"><label>Date hired</label><input type="date" name="date_hired" value="{{ old('date_hired', now()->toDateString()) }}"></div>
                        <div class="field"><label>Probation (months)</label><input type="number" min="1" max="24" name="probation_months" value="{{ old('probation_months', 6) }}"></div>
                        <div class="field"><label>Tax status</label><input name="tax_status" value="{{ old('tax_status', 'S') }}" maxlength="10" required></div>
                        <div class="field full"><button class="btn primary">Create employee record</button></div>
                    </form>
                </div>
            @endunless
        </div>
    @endif

    <div class="card">
        <div class="card-head"><h2>History</h2></div>
        <div class="card-body">
            @forelse($applicant->activities as $activity)
                <div style="border-bottom:1px solid var(--border);padding:8px 0">
                    <div class="small muted">{{ $activity->created_at->format('M d, Y g:i A') }} · {{ $activity->user?->name ?? 'System' }}</div>
                    <div>{{ $activity->notes }}</div>
                </div>
            @empty
                <p class="muted">No history yet.</p>
            @endforelse
        </div>
    </div>
@endsection
