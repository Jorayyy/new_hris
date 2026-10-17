@extends('layouts.app')

@section('title', $vacancy->exists ? 'Edit vacancy' : 'New vacancy')
@section('content')
    <form method="POST" action="{{ $vacancy->exists ? route('vacancies.update', $vacancy) : route('vacancies.store') }}">
        @csrf
        @if($vacancy->exists) @method('PUT') @endif

        <div class="card">
            <div class="card-head">
                <h2>{{ $vacancy->exists ? 'Edit vacancy' : 'New vacancy' }}</h2>
                <div class="btn-row">
                    <a class="btn sm ghost" href="{{ $vacancy->exists ? route('vacancies.show', $vacancy) : route('vacancies.index') }}">Cancel</a>
                    <button class="btn primary">{{ $vacancy->exists ? 'Save changes' : 'Create vacancy' }}</button>
                </div>
            </div>
            <div class="form-grid card-section">
                <div class="field full"><label>Title</label><input name="title" value="{{ old('title', $vacancy->title) }}" required></div>

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
                <div class="field"><label>Employment type</label>
                    <select name="employment_type">
                        @foreach(\App\Models\JobVacancy::EMPLOYMENT_TYPES as $value => $label)
                            <option value="{{ $value }}" @selected(old('employment_type', $vacancy->employment_type) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field"><label>Openings</label><input type="number" min="1" max="999" name="openings" value="{{ old('openings', $vacancy->openings) }}" required></div>
                <div class="field"><label>Status</label>
                    <select name="status">
                        @foreach(\App\Models\JobVacancy::STATUSES as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $vacancy->status) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field"><label>Posted on</label><input type="date" name="posted_on" value="{{ old('posted_on', $vacancy->posted_on?->toDateString() ?? now()->toDateString()) }}"></div>
                <div class="field"><label>Closes on</label><input type="date" name="closes_on" value="{{ old('closes_on', $vacancy->closes_on?->toDateString()) }}"></div>
                <div class="field full"><label>Description</label><textarea name="description" rows="4">{{ old('description', $vacancy->description) }}</textarea></div>
                <div class="field full"><label>Requirements</label><textarea name="requirements" rows="4">{{ old('requirements', $vacancy->requirements) }}</textarea></div>
            </div>
        </div>
    </form>
@endsection
