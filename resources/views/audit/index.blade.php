@extends('layouts.app')

@section('title', 'Audit Log')
@section('content')
    <div class="card">
        <div class="card-head">
            <h2>Audit log</h2>
            <form class="filters" method="GET">
                <div class="field">
                    <select name="action">
                        <option value="">All actions</option>
                        @foreach($actions as $action)
                            <option value="{{ $action }}" @selected(request('action') === $action)>{{ ucfirst($action) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <select name="user">
                        <option value="">All users</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" @selected((int) request('user') === $user->id)>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="btn">Filter</button>
                <a class="btn" href="{{ route('audit.index') }}">Reset</a>
            </form>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>When</th><th>User</th><th>Action</th><th>Description</th><th>IP</th></tr></thead>
                <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td class="small">{{ $log->created_at?->format('M d, Y h:i A') }}</td>
                        <td>{{ $log->user?->name ?? 'System' }}</td>
                        <td><span class="badge gray">{{ $log->action }}</span></td>
                        <td>{{ $log->description }}</td>
                        <td class="small muted">{{ $log->ip }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">No activity recorded yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="pagination">{{ $logs->links() }}</div>
@endsection
