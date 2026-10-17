@extends('layouts.app')

@section('title', 'Biometrics')
@section('content')
    @if(session('new_device_token'))
        <div class="alert ok">
            <strong>Device token (copy it now — it will not be shown again):</strong>
            <div style="font-family:monospace;word-break:break-all;margin-top:6px">{{ session('new_device_token') }}</div>
        </div>
    @endif

    <div class="card" style="max-width:860px">
        <div class="card-head">
            <h2>Import from device</h2>
        </div>
        <div class="card-body">
            <p class="muted small">
                Copy the attendance file off the biometric device (USB or the vendor's export) and upload it here.
                Rows are matched by employee number, grouped per work date, then processed by the DTR engine.
            </p>
            <table style="margin-bottom:16px">
                <thead><tr><th>Format</th><th>Columns</th></tr></thead>
                <tbody>
                    <tr>
                        <td>Paired</td>
                        <td class="muted small">employee no, date, time in, time out <br>e.g. <code>EMP0001, 2026-01-15, 08:00, 17:00</code></td>
                    </tr>
                    <tr>
                        <td>One row per punch</td>
                        <td class="muted small">employee no, name, date, time, state <br>e.g. <code>EMP0001, Dela Cruz Juan, 2026-01-15, 08:00, Check-in</code></td>
                    </tr>
                </tbody>
            </table>
            <p class="muted small">
                Dates are read as <code>YYYY-MM-DD</code> or <code>DD/MM/YYYY</code>; times as <code>HH:MM</code>,
                <code>HH:MM:SS</code> or <code>H:MM AM/PM</code>. Header and trailer lines are ignored.
                For per-punch files the earliest time of the day becomes time in and the latest becomes time out.
            </p>
            <form method="POST" action="{{ route('biometric-devices.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="field"><label>Device export file</label><input type="file" name="file" accept=".csv,.txt,.dat,.log,text/plain,text/csv" required></div>
                <div class="btn-row">
                    <button class="btn primary">Upload &amp; process</button>
                    <a class="btn" href="{{ route('attendance.index') }}">View DTR</a>
                </div>
            </form>
        </div>
    </div>

    @if($report = session('import_report'))
        <div class="card" style="max-width:860px">
            <div class="card-head">
                <h2>Import result</h2>
                <span class="muted small">{{ $report['created'] }} new · {{ $report['updated'] }} updated · {{ $report['problem_count'] }} skipped</span>
            </div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Line</th><th>Row</th><th>Why it was skipped</th></tr></thead>
                    <tbody>
                    @forelse($report['problems'] as $problem)
                        <tr>
                            <td class="muted small">{{ $problem['line'] ?: '—' }}</td>
                            <td class="small" style="font-family:monospace">{{ $problem['raw'] ?: '—' }}</td>
                            <td>{{ $problem['reason'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="muted">Every row was imported.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-head">
            <h2>Devices pushing over the network</h2>
            <span class="muted small">Only needed for devices that POST to <code>/api/biometric/punch</code> with their token</span>
        </div>
        <form method="POST" action="{{ route('biometric-devices.store') }}" class="form-grid card-section">
            @csrf
            <div class="field"><label>Name</label><input name="name" value="{{ old('name') }}" required></div>
            <div class="field"><label>Branch</label>
                <select name="branch_id">
                    <option value="">—</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" @selected(old('branch_id') == $branch->id)>{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field full"><button class="btn primary">Register device</button></div>
        </form>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Name</th><th>Branch</th><th>Status</th><th>Last seen</th><th></th></tr></thead>
                <tbody>
                @forelse($devices as $device)
                    <tr>
                        <td style="font-weight:600">{{ $device->name }}</td>
                        <td>{{ $device->branch?->name ?? '—' }}</td>
                        <td><span class="badge {{ $device->is_active ? 'green' : 'gray' }}">{{ $device->is_active ? 'Active' : 'Disabled' }}</span></td>
                        <td class="small">{{ $device->last_seen_at?->diffForHumans() ?? 'Never' }}</td>
                        <td class="btn-row">
                            <form method="POST" action="{{ route('biometric-devices.regenerate', $device) }}" onsubmit="return confirm('Regenerate the token? The device must be reconfigured.')">
                                @csrf
                                <button class="btn sm">Regenerate token</button>
                            </form>
                            <form method="POST" action="{{ route('biometric-devices.update', $device) }}">
                                @csrf @method('PUT')
                                <input type="hidden" name="is_active" value="{{ $device->is_active ? 0 : 1 }}">
                                <button class="btn sm">{{ $device->is_active ? 'Disable' : 'Enable' }}</button>
                            </form>
                            <form method="POST" action="{{ route('biometric-devices.destroy', $device) }}" onsubmit="return confirm('Remove this device?')">
                                @csrf @method('DELETE')
                                <button class="btn sm danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">No devices registered.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-head">
            <h2>Recent punches</h2>
            <span class="muted small">From uploads and network devices</span>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Employee</th><th>Branch</th><th>Work date</th><th>Time in</th><th>Time out</th><th>Updated</th></tr></thead>
                <tbody>
                @forelse($punches as $punch)
                    <tr>
                        <td style="font-weight:600">{{ $punch->employee?->full_name }}</td>
                        <td>{{ $punch->branch?->name ?? '—' }}</td>
                        <td>{{ $punch->work_date?->format('M d, Y') }}</td>
                        <td>{{ $punch->time_in?->format('H:i') ?? '—' }}</td>
                        <td>{{ $punch->time_out?->format('H:i') ?? '—' }}</td>
                        <td class="small muted">{{ $punch->updated_at?->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted">No biometric punches yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
