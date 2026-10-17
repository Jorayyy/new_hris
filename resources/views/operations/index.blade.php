@extends('layouts.app')

@section('title', 'Operations')
@section('content')
    <div class="grid cols-2">
        <div class="card">
            <div class="card-head"><h2>Queue</h2></div>
            <div class="card-body">
                <dl class="kv">
                    <dt>Pending jobs</dt><dd>{{ number_format($pendingJobs) }}</dd>
                    <dt>Failed jobs</dt><dd>{{ number_format($failedJobs) }}</dd>
                </dl>
                <p class="muted small">Notifications and other background work are queued. A worker must be running: <code>php artisan queue:work</code>. The queue drains every time the scheduler fires.</p>
                @if($failedJobs > 0)
                    <form method="POST" action="{{ route('operations.retry') }}">
                        @csrf
                        <button class="btn primary">Retry all failed jobs</button>
                    </form>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-head"><h2>Scheduled tasks</h2></div>
            <div class="card-body">
                <dl class="kv">
                    <dt>Last scheduler run</dt>
                    <dd>
                        @if($scheduleLastRun)
                            <span class="badge {{ now()->diffInMinutes(\Illuminate\Support\Carbon::parse($scheduleLastRun), true) > 5 ? 'red' : 'green' }}">{{ \Illuminate\Support\Carbon::parse($scheduleLastRun)->diffForHumans() }}</span>
                        @else
                            <span class="badge amber">No heartbeat yet</span>
                        @endif
                    </dd>
                </dl>
                <p class="muted small">Cron must call <code>php artisan schedule:run</code> every minute. Daily tasks: absences at 11:30 PM, document and regularization alerts at 7:00 AM, backup at 2:00 AM.</p>
            </div>
        </div>
    </div>

    <div class="grid cols-2">
        <div class="card">
            <div class="card-head"><h2>Backups</h2></div>
            <div class="card-body">
                @if($backups->isEmpty())
                    <p class="muted">No backup found. Run <code>php artisan hris:backup</code> and set its output as a cron task.</p>
                @else
                    <dl class="kv">
                        @foreach($backups as $backup)
                            <dt>{{ $backup['name'] }}</dt><dd>{{ $backup['size'] }}</dd>
                        @endforeach
                    </dl>
                    <p class="muted small">Stored on this server in <code>storage/app/backups</code>. Keep a copy off the server.</p>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-head"><h2>Configuration</h2></div>
            <div class="card-body">
                <table>
                    <tbody>
                    @foreach($checks as [$label, $value, $state])
                        <tr>
                            <td>{{ $label }}</td>
                            <td class="small">{{ $value }}</td>
                            <td class="right"><span class="badge {{ $state === 'ok' ? 'green' : 'amber' }}">{{ $state === 'ok' ? 'OK' : 'Check' }}</span></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if($recentFailures->isNotEmpty())
        <div class="card">
            <div class="card-head"><h2>Recent failures</h2></div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>When</th><th>Queue</th><th>Error</th></tr></thead>
                    <tbody>
                    @foreach($recentFailures as $failure)
                        <tr>
                            <td class="small">{{ \Illuminate\Support\Carbon::parse($failure->failed_at)->format('M d, Y h:i A') }}</td>
                            <td><span class="badge gray">{{ $failure->queue }}</span></td>
                            <td class="small muted">{{ \Illuminate\Support\Str::limit(\Illuminate\Support\Str::before($failure->exception, "\n"), 160) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
