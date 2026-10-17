@extends('layouts.app')

@section('title', 'Case #'.$case->id)
@section('back', route('me.cases'))
@section('content')
    @php
        $tone = ['resolved' => 'green', 'closed' => 'gray', 'awaiting_response' => 'amber'];
        $actionTone = ['filed' => 'gray', 'note' => 'amber', 'response' => 'amber', 'decision' => 'green'];
    @endphp
    <div class="card" style="max-width:820px">
        <div class="card-head">
            <h2>{{ $case->subject }}</h2>
            <span class="badge {{ $tone[$case->status] ?? 'amber' }}">{{ $case->status_label }}</span>
        </div>
        <div class="card-body">
            <table>
                <tbody>
                    <tr><th>Type</th><td>{{ $case->type_label }}</td></tr>
                    <tr><th>Incident date</th><td>{{ $case->incident_date?->format('M d, Y') ?? '—' }}</td></tr>
                    <tr><th>Response due</th><td>{{ $case->response_due_on?->format('M d, Y') ?? '—' }}</td></tr>
                    <tr><th>Filed by</th><td>{{ $case->filer?->name ?? '—' }}</td></tr>
                    <tr><th>Sanction</th><td>{{ $case->sanction ?: '—' }}</td></tr>
                </tbody>
            </table>
            @if($case->details)
                <p style="margin-top:14px;white-space:pre-line">{{ $case->details }}</p>
            @endif
            @if($case->decision)
                <div class="alert ok" style="margin-top:14px;white-space:pre-line">{{ $case->decision }}</div>
            @endif

            <h3 style="margin-top:20px">Timeline</h3>
            @forelse($case->actions as $action)
                <div style="border-left:2px solid var(--border);padding:0 0 14px 14px;margin-bottom:14px">
                    <div class="row-between">
                        <strong>{{ $action->type === 'response' ? 'You' : ($action->user?->name ?? 'HR') }}</strong>
                        <span class="badge {{ $actionTone[$action->type] ?? 'gray' }}">{{ ucfirst($action->type) }}</span>
                    </div>
                    <div class="small muted">{{ $action->created_at->format('M d, Y g:i A') }}</div>
                    <div style="margin-top:6px;white-space:pre-line">{{ $action->body }}</div>
                    @if($action->file_path)
                        <div style="margin-top:6px"><a href="{{ route('me.cases.file', [$case->id, $action->id]) }}" target="_blank">Attachment</a></div>
                    @endif
                </div>
            @empty
                <p class="muted small">No entries yet.</p>
            @endforelse

            @if($case->isOpenForResponse())
                <form method="POST" action="{{ route('me.cases.respond', $case) }}" style="border-top:1px solid var(--border);padding-top:14px">
                    @csrf
                    <div class="field">
                        <label>Your written explanation</label>
                        <textarea name="body" rows="5" required></textarea>
                    </div>
                    <button class="btn primary">Submit explanation</button>
                </form>
            @endif
        </div>
    </div>
@endsection
