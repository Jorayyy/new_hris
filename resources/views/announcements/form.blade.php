@extends('layouts.app')

@section('title', $announcement->exists ? 'Edit announcement' : 'New announcement')
@section('content')
    <div class="card" style="max-width:680px">
        <div class="card-head"><h2>{{ $announcement->exists ? 'Edit announcement' : 'New announcement' }}</h2></div>
        <div class="card-body">
            <form method="POST" action="{{ $announcement->exists ? route('announcements.update', $announcement) : route('announcements.store') }}">
                @csrf
                @if($announcement->exists) @method('PUT') @endif
                <div class="form-grid">
                    <div class="field">
                        <label>Branch</label>
                        <select name="branch_id">
                            <option value="">All branches</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" @selected(old('branch_id', $announcement->branch_id) == $branch->id)>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label>Published at</label>
                        <input type="datetime-local" name="published_at" value="{{ old('published_at', $announcement->published_at?->format('Y-m-d\TH:i')) }}">
                    </div>
                    <div class="field full">
                        <label>Title</label>
                        <input type="text" name="title" value="{{ old('title', $announcement->title) }}" required>
                    </div>
                    <div class="field full">
                        <label>Body</label>
                        <textarea name="body" rows="5" required>{{ old('body', $announcement->body) }}</textarea>
                    </div>
                </div>
                <div class="btn-row">
                    <button class="btn primary">Save announcement</button>
                    <a class="btn" href="{{ route('announcements.index') }}">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
