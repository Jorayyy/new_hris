@extends('layouts.app')

@section('title', 'QA scorecard')
@section('back', route('qa.index'))
@section('content')
    <div class="card">
        <div class="card-head">
            <div>
                <h2>{{ $evaluation->employee?->full_name }}</h2>
                <div class="muted small">{{ $evaluation->employee?->employee_no }} · {{ $evaluation->employee?->branch?->name ?? '—' }} · evaluated {{ $evaluation->evaluated_on->format('M d, Y') }}</div>
            </div>
            <div class="btn-row">
                <span class="badge {{ $evaluation->statusTone() }}">{{ $evaluation->statusLabel() }}</span>
                <a class="btn sm" href="{{ route('employees.show', $evaluation->employee_id) }}">Employee</a>
                <a class="btn sm" href="{{ route('qa.edit', $evaluation) }}">Edit</a>
                <form method="POST" action="{{ route('qa.destroy', $evaluation) }}" onsubmit="return confirm('Delete this scorecard?')">
                    @csrf @method('DELETE')
                    <button class="btn sm danger">Delete</button>
                </form>
            </div>
        </div>
        <div class="card-body">
            <dl class="kv">
                <dt>Total score</dt><dd style="font-weight:600">{{ $evaluation->score !== null ? number_format((float) $evaluation->score, 2).'%' : '—' }} <span class="muted small">(passing {{ $passing }}%)</span></dd>
                <dt>Evaluator</dt><dd>{{ $evaluation->evaluator?->name ?? '—' }}</dd>
                <dt>Reference</dt><dd>{{ $evaluation->reference ?: '—' }}</dd>
            </dl>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Criterion</th><th class="num">Weight</th><th class="num">Score</th></tr></thead>
                    <tbody>
                    @forelse($evaluation->scores as $score)
                        <tr>
                            <td>{{ $score->criterion?->name }}</td>
                            <td class="num">{{ rtrim(rtrim(number_format((float) $score->criterion?->weight, 2), '0'), '.') }}%</td>
                            <td class="num">{{ number_format((float) $score->score, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="muted">No criteria were scored.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if($evaluation->remarks)
                <h3>Strengths / remarks</h3>
                <p>{{ $evaluation->remarks }}</p>
            @endif
            @if($evaluation->coaching_notes)
                <h3>Coaching notes</h3>
                <p>{{ $evaluation->coaching_notes }}</p>
            @endif
        </div>
    </div>
@endsection
