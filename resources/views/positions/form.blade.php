@extends('layouts.app')

@section('title', $position->exists ? 'Edit position' : 'New position')
@section('content')
    <div class="card" style="max-width:680px">
        <div class="card-head"><h2>{{ $position->exists ? 'Edit position' : 'New position' }}</h2></div>
        <div class="card-body">
            <form method="POST" action="{{ $position->exists ? route('positions.update', $position) : route('positions.store') }}">
                @csrf
                @if($position->exists) @method('PUT') @endif
                <div class="form-grid">
                    <div class="field">
                        <label>Title</label>
                        <input type="text" name="title" value="{{ old('title', $position->title) }}" required>
                    </div>
                    <div class="field">
                        <label>Code</label>
                        <input type="text" name="code" value="{{ old('code', $position->code) }}">
                    </div>
                    <div class="field">
                        <label>Department</label>
                        <select name="department_id">
                            <option value="">—</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}" @selected(old('department_id', $position->department_id) == $department->id)>{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label>Default salary</label>
                        <input type="number" step="0.01" name="default_salary" value="{{ old('default_salary', $position->default_salary) }}">
                    </div>
                    <div class="field full">
                        <label class="check">
                            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $position->is_active ?? true))> Active
                        </label>
                    </div>
                </div>
                <div class="btn-row">
                    <button class="btn primary">Save</button>
                    <a class="btn" href="{{ route('positions.index') }}">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
