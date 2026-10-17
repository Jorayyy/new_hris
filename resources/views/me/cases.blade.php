@extends('layouts.app')

@section('title', 'My Cases')
@section('content')
    @php $tone = ['resolved' => 'green', 'closed' => 'gray', 'awaiting_response' => 'amber']; @endphp
    <div class="card">
        <div class="card-head"><h2>My cases</h2></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Subject</th><th>Type</th><th>Incident</th><th>Status</th><th>Response due</th><th></th></tr></thead>
                <tbody>
                @forelse($cases as $case)
                    <tr>
                        <td style="font-weight:600">{{ $case->subject }}</td>
                        <td>{{ $case->type_label }}</td>
                        <td>{{ $case->incident_date?->format('M d, Y') ?? '—' }}</td>
                        <td><span class="badge {{ $tone[$case->status] ?? 'amber' }}">{{ $case->status_label }}</span></td>
                        <td>{{ $case->response_due_on?->format('M d, Y') ?? '—' }}</td>
                        <td><a class="btn sm" href="{{ route('me.cases.show', $case) }}">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted">You have no cases.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $cases->links() }}
    </div>
@endsection
