@extends('layouts.app')

@section('title', 'Case #'.$case->id)
@section('back', route('cases.index'))
@section('content')
    @php
        $tone = ['resolved' => 'green', 'closed' => 'gray', 'awaiting_response' => 'amber'];
        $sevTone = ['high' => 'red', 'medium' => 'amber', 'low' => 'gray'];
        $actionTone = ['filed' => 'gray', 'note' => 'amber', 'response' => 'amber', 'decision' => 'green'];
    @endphp
    <div class="grid cols-2">
        <div class="card">
            <div class="card-head"><h2>{{ $case->subject }}</h2></div>
            <div class="card-body">
                <table>
                    <tbody>
                        <tr><th>Employee</th><td>{{ $case->employee?->full_name }} <span class="muted small">{{ $case->employee?->employee_no }}</span></td></tr>
                        <tr><th>Branch</th><td>{{ $case->branch?->name ?? '—' }}</td></tr>
                        <tr><th>Type</th><td>{{ $case->type_label }}</td></tr>
                        <tr><th>Severity</th><td><span class="badge {{ $sevTone[$case->severity] ?? 'gray' }}">{{ ucfirst($case->severity) }}</span></td></tr>
                        <tr><th>Status</th><td><span class="badge {{ $tone[$case->status] ?? 'amber' }}">{{ $case->status_label }}</span></td></tr>
                        <tr><th>Incident date</th><td>{{ $case->incident_date?->format('M d, Y') ?? '—' }}</td></tr>
                        <tr><th>Response due</th><td>{{ $case->response_due_on?->format('M d, Y') ?? '—' }}</td></tr>
                        <tr><th>Filed by</th><td>{{ $case->filer?->name ?? '—' }}</td></tr>
                        @if($case->decided_at)
                            <tr><th>Decided by</th><td>{{ $case->decider?->name ?? '—' }} <span class="muted small">{{ $case->decided_at->format('M d, Y') }}</span></td></tr>
                            <tr><th>Sanction</th><td>{{ $case->sanction ?: '—' }}</td></tr>
                        @endif
                    </tbody>
                </table>
                @if($case->details)
                    <p style="margin-top:14px;white-space:pre-line">{{ $case->details }}</p>
                @endif
                @if($case->decision)
                    <div class="alert ok" style="margin-top:14px;white-space:pre-line">{{ $case->decision }}</div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-head"><h2>Decision</h2></div>
            <div class="card-body">
                <form method="POST" action="{{ route('cases.decide', $case) }}">
                    @csrf
                    <div class="field">
                        <label>Status</label>
                        <select name="status" required>
                            @foreach($statuses as $key => $label)
                                <option value="{{ $key }}" @selected($case->status === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label>Decision</label>
                        <textarea name="decision" rows="4">{{ $case->decision }}</textarea>
                    </div>
                    <div class="field">
                        <label>Sanction</label>
                        <input type="text" name="sanction" value="{{ $case->sanction }}" maxlength="200" placeholder="e.g. Written warning">
                    </div>
                    <button class="btn primary">Save decision</button>
                </form>
            </div>
        </div>
    </div>

    <div class="card" style="margin-top:16px">
        <div class="card-head"><h2>Case timeline</h2></div>
        <div class="card-body">
            @forelse($case->actions as $action)
                <div style="border-left:2px solid var(--border);padding:0 0 14px 14px;margin-bottom:14px">
                    <div class="row-between">
                        <strong>{{ $action->user?->name ?? 'Employee' }}</strong>
                        <span class="badge {{ $actionTone[$action->type] ?? 'gray' }}">{{ ucfirst($action->type) }}</span>
                    </div>
                    <div class="small muted">{{ $action->created_at->format('M d, Y g:i A') }}</div>
                    <div style="margin-top:6px;white-space:pre-line">{{ $action->body }}</div>
                    @if($action->file_path)
                        <div style="margin-top:6px"><a href="{{ \Illuminate\Support\Facades\Storage::url($action->file_path) }}" target="_blank">Attachment</a></div>
                    @endif
                </div>
            @empty
                <p class="muted small">No entries yet.</p>
            @endforelse

            <form method="POST" action="{{ route('cases.actions.store', $case) }}" enctype="multipart/form-data" style="border-top:1px solid var(--border);padding-top:14px">
                @csrf
                <div class="field">
                    <label>Add a note</label>
                    <textarea name="body" rows="3" required></textarea>
                </div>
                <div class="field"><label>Attachment <span class="muted small">(PDF or image, max 5&nbsp;MB)</span></label><input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"></div>
                <button class="btn primary">Add to timeline</button>
            </form>
        </div>
    </div>
@endsection
