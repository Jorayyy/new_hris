@extends('layouts.app')

@section('title', $branch->exists ? 'Edit branch' : 'New branch')
@section('content')
    <div class="card" style="max-width:680px">
        <div class="card-head"><h2>{{ $branch->exists ? 'Edit branch' : 'New branch' }}</h2></div>
        <div class="card-body">
            <form method="POST" action="{{ $branch->exists ? route('branches.update', $branch) : route('branches.store') }}">
                @csrf
                @if($branch->exists) @method('PUT') @endif
                <div class="form-grid">
                    <div class="field">
                        <label>Name</label>
                        <input type="text" name="name" value="{{ old('name', $branch->name) }}" required>
                    </div>
                    <div class="field">
                        <label>Code</label>
                        <input type="text" name="code" value="{{ old('code', $branch->code) }}" required>
                    </div>
                    <div class="field full">
                        <label>Address</label>
                        <input type="text" name="address" value="{{ old('address', $branch->address) }}">
                    </div>
                    <div class="field full">
                        <label style="display:flex;align-items:center;gap:8px;font-weight:500">
                            <input type="checkbox" name="is_active" value="1" style="width:auto" @checked(old('is_active', $branch->is_active ?? true))> Active
                        </label>
                    </div>
                </div>
                <div class="btn-row">
                    <button class="btn primary">Save branch</button>
                    <a class="btn" href="{{ route('branches.index') }}">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
