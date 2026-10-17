@extends('layouts.app')

@section('title', 'My Scorecards')
@section('content')
    <div class="card">
        <div class="card-head">
            <h2>My QA scorecards</h2>
            <span class="muted">Average: {{ $average !== null ? number_format((float) $average, 2).'%' : '—' }} · passing {{ $passing }}%</span>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Date</th><th class="num">Score</th><th>Rating</th><th>Criteria</th><th>Coaching notes</th></tr></thead>
                <tbody>
                @forelse($evaluations as $evaluation)
                    <tr>
                        <td>{{ $evaluation->evaluated_on->format('M d, Y') }}</td>
                        <td class="num" style="font-weight:600">{{ $evaluation->score !== null ? number_format((float) $evaluation->score, 2) : '—' }}</td>
                        <td><span class="badge {{ $evaluation->statusTone() }}">{{ $evaluation->statusLabel() }}</span></td>
                        <td class="muted small">
                            @foreach($evaluation->scores as $score)
                                {{ $score->criterion?->name }}: {{ number_format((float) $score->score, 2) }}@if(! $loop->last) · @endif
                            @endforeach
                        </td>
                        <td class="small">{{ $evaluation->coaching_notes ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">No scorecards recorded yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $evaluations->links() }}
    </div>
@endsection
