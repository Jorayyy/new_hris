@extends('layouts.app')

@section('title', 'Notifications')
@section('content')
    <div class="card" style="max-width:860px">
        <div class="card-head">
            <h2>Notifications</h2>
            @if(auth()->user()->unreadNotifications()->count())
                <form method="POST" action="{{ route('notifications.read-all') }}">@csrf<button class="btn sm">Mark all as read</button></form>
            @endif
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Notification</th><th>When</th><th></th></tr></thead>
                <tbody>
                @forelse($notifications as $notification)
                    <tr>
                        <td>
                            <a href="{{ route('notifications.read', $notification->id) }}" style="font-weight:{{ $notification->read_at ? '500' : '700' }}">
                                <span class="ico">{{ $notification->data['icon'] ?? '•' }}</span> {{ $notification->data['title'] ?? 'Notification' }}
                            </a>
                            <div class="small muted">{{ $notification->data['body'] ?? '' }}</div>
                        </td>
                        <td class="muted small">{{ $notification->created_at->diffForHumans() }}</td>
                        <td>@unless($notification->read_at)<span class="badge green">New</span>@endunless</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="muted">No notifications.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div style="margin-top:12px">{{ $notifications->links() }}</div>
@endsection
