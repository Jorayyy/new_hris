@extends('layouts.app')

@section('title', $evaluation->exists ? 'Edit scorecard' : 'New QA scorecard')
@section('back', route('qa.index'))
@section('content')
    <form method="POST" action="{{ $evaluation->exists ? route('qa.update', $evaluation) : route('qa.store') }}" class="card">
        @csrf
        @if($evaluation->exists) @method('PUT') @endif
        <div class="card-head">
            <h2>{{ $evaluation->exists ? 'Edit scorecard' : 'New QA scorecard' }}</h2>
            <span class="muted">Total is the weighted average · passing score {{ $passing }}%</span>
        </div>
        <div class="card-body">
            <div class="form-grid">
                <div class="field"><label>Employee</label>
                    <select name="employee_id" required>
                        <option value="">Select employee…</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" @selected((int) old('employee_id', $evaluation->employee_id) === $employee->id)>{{ $employee->full_name }} ({{ $employee->employee_no }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="field"><label>Evaluation date</label><input type="date" name="evaluated_on" value="{{ old('evaluated_on', optional($evaluation->evaluated_on)->format('Y-m-d') ?? now()->toDateString()) }}" required></div>
                <div class="field"><label>Reference (optional)</label><input name="reference" value="{{ old('reference', $evaluation->reference) }}" placeholder="Call ID, ticket no."></div>
            </div>

            <h3>Criteria scores (0–100)</h3>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Criterion</th><th class="num">Weight</th><th class="num">Score</th></tr></thead>
                    <tbody>
                    @forelse($criteria as $criterion)
                        <tr>
                            <td>{{ $criterion->name }}</td>
                            <td class="num">{{ rtrim(rtrim(number_format((float) $criterion->weight, 2), '0'), '.') }}%</td>
                            <td class="num"><input type="number" step="0.01" min="0" max="100" name="scores[{{ $criterion->id }}]" value="{{ old("scores.{$criterion->id}", $evaluation->scores->firstWhere('qa_criterion_id', $criterion->id)?->score) }}" style="width:110px"></td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="muted">No QA criteria yet. <a href="{{ route('qa.criteria') }}">Add criteria first.</a></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="form-grid">
                <div class="field full"><label>Strengths / remarks</label><textarea name="remarks" rows="3">{{ old('remarks', $evaluation->remarks) }}</textarea></div>
                <div class="field full"><label>Coaching notes</label><textarea name="coaching_notes" rows="3">{{ old('coaching_notes', $evaluation->coaching_notes) }}</textarea></div>
            </div>
            <div class="btn-row"><button class="btn primary">{{ $evaluation->exists ? 'Save changes' : 'Save scorecard' }}</button></div>
        </div>
    </form>
@endsection
