@extends('layouts.app')

@section('title', 'QA Criteria')
@section('back', route('qa.index'))
@section('content')
    <div class="card">
        <div class="card-head">
            <h2>QA Criteria</h2>
            <span class="muted">Scores are averaged using each criterion's weight.</span>
        </div>
        <form method="POST" action="{{ route('qa.settings') }}" class="filters">
            @csrf @method('PUT')
            <div class="field"><label>Passing score (%)</label><input type="number" step="0.01" min="1" max="100" name="passing_score" value="{{ $passing }}" class="w-sm"></div>
            <button class="btn primary">Save</button>
        </form>
        <form method="POST" action="{{ route('qa-criteria.store') }}" class="form-grid" style="padding:0 16px 16px">
            @csrf
            <div class="field"><label>Name</label><input name="name" value="{{ old('name') }}" placeholder="e.g. Product knowledge" required></div>
            <div class="field"><label>Weight (%)</label><input type="number" step="0.01" min="0.01" max="100" name="weight" value="{{ old('weight', 100) }}" required></div>
            <div class="field"><label>Sort order</label><input type="number" min="0" max="999" name="sort" value="{{ old('sort', 0) }}"></div>
            <div class="field"><label class="check" style="margin-top:22px"><input type="checkbox" name="is_active" value="1" checked> Active</label></div>
            <div class="field full"><button class="btn primary">Add criterion</button></div>
        </form>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Name</th><th class="num">Weight %</th><th class="num">Sort</th><th>Active</th><th class="num">Scores</th><th></th></tr></thead>
                <tbody>
                @forelse($criteria as $criterion)
                    <tr>
                        <td colspan="6" style="padding:0">
                            <form method="POST" action="{{ route('qa-criteria.update', $criterion) }}" class="row-form">
                                @csrf @method('PUT')
                                <input name="name" value="{{ $criterion->name }}" class="grow" required>
                                <input type="number" step="0.01" min="0.01" max="100" name="weight" value="{{ $criterion->weight }}" class="w-xs">
                                <input type="number" min="0" max="999" name="sort" value="{{ $criterion->sort }}" class="w-xs">
                                <label class="check"><input type="checkbox" name="is_active" value="1" @checked($criterion->is_active)> Active</label>
                                <span class="badge gray">{{ $criterion->scores_count }} scored</span>
                                <button class="btn sm danger" form="qa-delete-{{ $criterion->id }}">Delete</button>
                                <button class="btn sm primary">Save</button>
                            </form>
                            <form id="qa-delete-{{ $criterion->id }}" method="POST" action="{{ route('qa-criteria.destroy', $criterion) }}" onsubmit="return confirm('Delete this criterion?')">
                                @csrf @method('DELETE')
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted">No criteria yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $criteria->links() }}
    </div>
@endsection
