@extends('layouts.app')

@section('title', 'Employee Cases')
@section('content')
    @php
        $tone = ['resolved' => 'green', 'closed' => 'gray', 'awaiting_response' => 'amber'];
        $sevTone = ['high' => 'red', 'medium' => 'amber', 'low' => 'gray'];
    @endphp
    <div class="card">
        <div class="card-head">
            <h2>Employee relations cases</h2>
            <div class="btn-row">
                <a class="btn sm {{ $status === 'all' ? 'primary' : '' }}" href="{{ route('cases.index', ['status' => 'all']) }}">All</a>
                @foreach($statuses as $key => $label)
                    <a class="btn sm {{ $status === $key ? 'primary' : '' }}" href="{{ route('cases.index', ['status' => $key]) }}">{{ $label }}</a>
                @endforeach
                <a class="btn primary sm" href="{{ route('cases.create') }}">+ File a case</a>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" class="filters">
                <input type="hidden" name="status" value="{{ $status }}">
                <div class="field" style="min-width:240px">
                    <label>Search employee</label>
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Name or employee no.">
                </div>
                <button class="btn primary">Search</button>
                <a class="btn" href="{{ route('cases.index') }}">Reset</a>
            </form>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Employee</th><th>Type</th><th>Severity</th><th>Subject</th><th>Incident</th><th>Status</th><th>Filed by</th><th></th></tr></thead>
                <tbody>
                @forelse($cases as $case)
                    <tr>
                        <td style="font-weight:600">{{ $case->employee?->full_name }}<div class="small muted">{{ $case->employee?->employee_no }}</div></td>
                        <td>{{ $case->type_label }}</td>
                        <td><span class="badge {{ $sevTone[$case->severity] ?? 'gray' }}">{{ ucfirst($case->severity) }}</span></td>
                        <td>{{ $case->subject }}</td>
                        <td>{{ $case->incident_date?->format('M d, Y') ?? '—' }}</td>
                        <td><span class="badge {{ $tone[$case->status] ?? 'amber' }}">{{ $case->status_label }}</span></td>
                        <td class="small">{{ $case->filer?->name ?? '—' }}</td>
                        <td><a class="btn sm" href="{{ route('cases.show', $case) }}">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="muted">No cases.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $cases->links() }}
    </div>
@endsection
