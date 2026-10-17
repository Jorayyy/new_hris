@extends('layouts.app')

@section('title', 'Biometric Devices')
@section('content')
    @if(session('new_device_token'))
        <div class="alert ok">
            <strong>Device token (copy it now — it will not be shown again):</strong>
            <div style="font-family:monospace;word-break:break-all;margin-top:6px">{{ session('new_device_token') }}</div>
        </div>
    @endif

    <div class="card">
        <div class="card-head">
            <h2>Register device</h2>
        </div>
        <form method="POST" action="{{ route('biometric-devices.store') }}" class="form-grid" style="padding:16px">
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
    </div>

    <div class="card">
        <div class="card-head">
            <h2>Devices</h2>
        </div>
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
            <h2>Recent biometric punches</h2>
            <span class="muted small">POST /api/biometric/punch</span>
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
