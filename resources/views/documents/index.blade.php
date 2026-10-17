@extends('layouts.app')

@section('title', 'Document Compliance')
@section('content')
    @php
        $tone = ['missing' => 'red', 'expired' => 'red', 'expiring' => 'amber', 'ok' => 'green'];
        $label = ['missing' => 'Missing', 'expired' => 'Expired', 'expiring' => 'Expiring soon', 'ok' => 'OK'];
    @endphp

    <div class="card">
        <div class="card-head">
            <h2>201-File Document Compliance</h2>
            <div class="btn-row">
                <a class="btn sm {{ $view === 'expiring' ? 'primary' : '' }}" href="{{ route('documents.index', ['view' => 'expiring']) }}">Expiring / expired</a>
                <a class="btn sm {{ $view === 'missing' ? 'primary' : '' }}" href="{{ route('documents.index', ['view' => 'missing']) }}">Missing</a>
                <a class="btn sm" href="{{ route('document-types.index') }}">Document types</a>
            </div>
        </div>
        <form method="GET" class="filters">
            <input type="hidden" name="view" value="{{ $view }}">
            <div class="field"><label>Search</label><input name="q" value="{{ request('q') }}" placeholder="Name or employee no."></div>
            <div class="field"><label>Branch</label>
                <select name="branch">
                    <option value="">All branches</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" @selected((string) request('branch') === (string) $branch->id)>{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn primary">Filter</button>
        </form>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Employee</th><th>Branch</th><th>Document</th><th>Status</th><th>Expiry</th><th></th></tr></thead>
                <tbody>
                @forelse($issues as $issue)
                    <tr>
                        <td><a href="{{ route('employees.show', $issue['employee']) }}">{{ $issue['employee']->full_name }}</a> <span class="muted">{{ $issue['employee']->employee_no }}</span></td>
                        <td>{{ $issue['employee']->branch?->name ?? '—' }}</td>
                        <td>{{ $issue['type']?->name ?? $issue['document']?->type ?? '—' }}</td>
                        <td><span class="badge {{ $tone[$issue['state']] ?? 'gray' }}">{{ $label[$issue['state']] ?? ucfirst((string) $issue['state']) }}</span></td>
                        <td>{{ $issue['document']?->expires_at?->format('M d, Y') ?? '—' }}</td>
                        <td>
                            @if($issue['document'])
                                <a class="btn sm" href="{{ route('documents.file', $issue['document']) }}" target="_blank">Open</a>
                            @else
                                <a class="btn sm primary" href="{{ route('employees.show', $issue['employee']) }}">Upload</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="muted">
                            {{ $view === 'missing' ? 'No missing required documents. Every 201 file is complete.' : 'No documents expiring within '.App\Models\EmployeeDocument::EXPIRY_WARN_DAYS.' days.' }}
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $issues->links() }}
    </div>
@endsection
