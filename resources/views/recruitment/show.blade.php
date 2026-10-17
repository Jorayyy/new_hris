@extends('layouts.app')

@section('title', $vacancy->title)
@section('content')
    @php $canManage = auth()->user()->hasRole(\App\Models\User::HR_ROLES); @endphp

    <div class="card">
        <div class="card-head">
            <h2>{{ $vacancy->title }}</h2>
            <div class="btn-row">
                <span class="badge {{ $vacancy->statusTone() }}">{{ $vacancy->statusLabel() }}</span>
                @if($canManage)
                    <a class="btn sm" href="{{ route('vacancies.edit', $vacancy) }}">Edit</a>
                    <form method="POST" action="{{ route('vacancies.destroy', $vacancy) }}" onsubmit="return confirm('Delete this vacancy?')">
                        @csrf @method('DELETE')
                        <button class="btn sm danger">Delete</button>
                    </form>
                @endif
            </div>
        </div>
        <div class="card-body">
            <dl class="kv">
                <dt>Branch</dt><dd>{{ $vacancy->branch?->name ?? '—' }}</dd>
                <dt>Department</dt><dd>{{ $vacancy->department?->name ?? '—' }}</dd>
                <dt>Position</dt><dd>{{ $vacancy->position?->title ?? '—' }}</dd>
                <dt>Employment type</dt><dd>{{ $vacancy->employmentTypeLabel() }}</dd>
                <dt>Openings</dt><dd>{{ $vacancy->hiredCount() }} hired of {{ $vacancy->openings }}</dd>
                <dt>Posted</dt><dd>{{ $vacancy->posted_on?->format('M d, Y') ?? '—' }}</dd>
                <dt>Closes</dt><dd>{{ $vacancy->closes_on?->format('M d, Y') ?? '—' }}</dd>
                @if($vacancy->description)
                    <dt>Description</dt><dd>{!! nl2br(e($vacancy->description)) !!}</dd>
                @endif
                @if($vacancy->requirements)
                    <dt>Requirements</dt><dd>{!! nl2br(e($vacancy->requirements)) !!}</dd>
                @endif
            </dl>
        </div>
    </div>

    @if($canManage)
        <div class="card">
            <div class="card-head"><h2>Add applicant</h2><span class="muted">Starts in the Applied stage</span></div>
            <form method="POST" action="{{ route('applicants.store', $vacancy) }}" class="form-grid" style="padding:16px">
                @csrf
                <div class="field"><label>First name</label><input name="first_name" value="{{ old('first_name') }}" required></div>
                <div class="field"><label>Last name</label><input name="last_name" value="{{ old('last_name') }}" required></div>
                <div class="field"><label>Email</label><input type="email" name="email" value="{{ old('email') }}"></div>
                <div class="field"><label>Contact no.</label><input name="contact_no" value="{{ old('contact_no') }}"></div>
                <div class="field"><label>Source</label>
                    <select name="source">
                        <option value="">—</option>
                        @foreach(\App\Models\Applicant::SOURCES as $source)
                            <option value="{{ $source }}" @selected(old('source') === $source)>{{ $source }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field"><label>Applied on</label><input type="date" name="applied_on" value="{{ old('applied_on', now()->toDateString()) }}"></div>
                <div class="field full"><label>Notes</label><textarea name="notes" rows="2">{{ old('notes') }}</textarea></div>
                <div class="field full"><button class="btn primary">Add applicant</button></div>
            </form>
        </div>
    @endif

    <div class="card">
        <div class="card-head"><h2>Applicants</h2><span class="muted">{{ $vacancy->applicants->count() }} total</span></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Name</th><th>Contact</th><th>Source</th><th>Stage</th><th>Applied</th><th>Employee</th></tr></thead>
                <tbody>
                @forelse($vacancy->applicants as $applicant)
                    <tr>
                        <td><a href="{{ route('applicants.show', $applicant) }}" style="font-weight:600">{{ $applicant->full_name }}</a></td>
                        <td class="muted small">{{ $applicant->email ?: '—' }}@if($applicant->contact_no)<br>{{ $applicant->contact_no }}@endif</td>
                        <td class="muted small">{{ $applicant->source ?: '—' }}</td>
                        <td><span class="badge {{ $applicant->stageTone() }}">{{ $applicant->stageLabel() }}</span></td>
                        <td class="muted small">{{ $applicant->applied_on?->format('M d, Y') ?? '—' }}</td>
                        <td class="muted small">{{ $applicant->employee?->employee_no ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted">No applicants yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
