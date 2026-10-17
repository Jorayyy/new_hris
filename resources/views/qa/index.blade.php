@extends('layouts.app')

@section('title', 'QA Scorecards')
@section('content')
    <div class="card">
        <div class="card-head">
            <h2>QA Scorecards</h2>
            <div class="btn-row">
                <a class="btn sm" href="{{ route('qa.criteria') }}">Criteria</a>
                <a class="btn sm primary" href="{{ route('qa.create') }}">+ New scorecard</a>
            </div>
        </div>
        <form method="GET" class="filters">
            <div class="field"><label>Month</label><input type="month" name="month" value="{{ $month }}"></div>
            <div class="field"><label>Search</label><input name="q" value="{{ request('q') }}" placeholder="Name or employee no."></div>
            <div class="field"><label class="check"><input type="checkbox" name="below" value="1" @checked($below)> Below passing {{ $passing }}%</label></div>
            <button class="btn primary">Filter</button>
        </form>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Date</th><th>Employee</th><th>Reference</th><th class="num">Score</th><th>Rating</th><th>Evaluator</th><th></th></tr></thead>
                <tbody>
                @forelse($evaluations as $evaluation)
                    <tr>
                        <td>{{ $evaluation->evaluated_on->format('M d, Y') }}</td>
                        <td><a href="{{ route('qa.show', $evaluation) }}" style="font-weight:600">{{ $evaluation->employee?->full_name }}</a></td>
                        <td class="muted small">{{ $evaluation->reference ?: '—' }}</td>
                        <td class="num">{{ $evaluation->score !== null ? number_format((float) $evaluation->score, 2) : '—' }}</td>
                        <td><span class="badge {{ $evaluation->statusTone() }}">{{ $evaluation->statusLabel() }}</span></td>
                        <td class="muted small">{{ $evaluation->evaluator?->name ?? '—' }}</td>
                        <td><a class="btn sm ghost" href="{{ route('qa.edit', $evaluation) }}">Edit</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="muted">No scorecards for this month.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $evaluations->links() }}
    </div>

    @if($summary->isNotEmpty())
        <div class="card">
            <div class="card-head"><h2>Team average — {{ \Illuminate\Support\Carbon::parse($month.'-01')->format('F Y') }}</h2><span class="muted">Lowest scoring first</span></div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Employee</th><th>Branch</th><th class="num">Scorecards</th><th class="num">Average</th><th>Rating</th></tr></thead>
                    <tbody>
                    @foreach($summary as $employee)
                        @php $average = (float) $employee->average_score; @endphp
                        <tr>
                            <td>{{ $employee->full_name }}</td>
                            <td class="muted small">{{ $employee->branch?->name ?? '—' }}</td>
                            <td class="num">{{ $employee->evaluation_count }}</td>
                            <td class="num" style="font-weight:600">{{ number_format($average, 2) }}</td>
                            <td><span class="badge {{ $average >= 95 ? 'green' : ($average >= $passing ? 'blue' : 'red') }}">{{ $average >= 95 ? 'Excellent' : ($average >= $passing ? 'Passing' : 'Needs coaching') }}</span></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
