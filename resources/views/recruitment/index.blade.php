@extends('layouts.app')

@section('title', 'Recruitment')
@section('content')
    @php $canManage = auth()->user()->hasRole(\App\Models\User::HR_ROLES); @endphp

    <div class="card">
        <div class="card-head">
            <h2>Vacancies</h2>
            @if($canManage)
                <a class="btn sm primary" href="{{ route('vacancies.create') }}">+ New vacancy</a>
            @endif
        </div>
        <form method="GET" class="filters">
            <div class="field"><label>Status</label>
                <select name="status">
                    <option value="">All</option>
                    @foreach(\App\Models\JobVacancy::STATUSES as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field"><label>Search</label><input name="q" value="{{ request('q') }}" placeholder="Vacancy title"></div>
            <button class="btn primary">Filter</button>
        </form>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Vacancy</th><th>Branch</th><th>Department</th><th>Position</th><th>Type</th><th class="num">Filled</th><th class="num">Applicants</th><th>Status</th><th>Closes</th></tr></thead>
                <tbody>
                @forelse($vacancies as $vacancy)
                    <tr>
                        <td><a href="{{ route('vacancies.show', $vacancy) }}" style="font-weight:600">{{ $vacancy->title }}</a></td>
                        <td class="muted small">{{ $vacancy->branch?->name ?? '—' }}</td>
                        <td class="muted small">{{ $vacancy->department?->name ?? '—' }}</td>
                        <td class="muted small">{{ $vacancy->position?->title ?? '—' }}</td>
                        <td class="muted small">{{ $vacancy->employmentTypeLabel() }}</td>
                        <td class="num">{{ $vacancy->hired_count }} / {{ $vacancy->openings }}</td>
                        <td class="num">{{ $vacancy->applicants_count }}</td>
                        <td><span class="badge {{ $vacancy->statusTone() }}">{{ $vacancy->statusLabel() }}</span></td>
                        <td class="muted small">{{ $vacancy->closes_on?->format('M d, Y') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="muted">No vacancies yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $vacancies->links() }}
    </div>
@endsection
