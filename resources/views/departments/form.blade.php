@extends('layouts.app')

@section('title', $department->exists ? 'Edit department' : 'New department')
@section('content')
    <div class="card" style="max-width:680px">
        <div class="card-head"><h2>{{ $department->exists ? 'Edit department' : 'New department' }}</h2></div>
        <div class="card-body">
            <form method="POST" action="{{ $department->exists ? route('departments.update', $department) : route('departments.store') }}">
                @csrf
                @if($department->exists) @method('PUT') @endif
                <div class="form-grid">
                    <div class="field">
                        <label>Name</label>
                        <input type="text" name="name" value="{{ old('name', $department->name) }}" required>
                    </div>
                    <div class="field">
                        <label>Code</label>
                        <input type="text" name="code" value="{{ old('code', $department->code) }}">
                    </div>
                    <div class="field">
                        <label>Branch</label>
                        <select name="branch_id">
                            <option value="">All branches</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" @selected(old('branch_id', $department->branch_id) == $branch->id)>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label class="check">
                            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $department->is_active ?? true))> Active
                        </label>
                    </div>
                </div>
                <div class="btn-row">
                    <button class="btn primary">Save</button>
                    <a class="btn" href="{{ route('departments.index') }}">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
