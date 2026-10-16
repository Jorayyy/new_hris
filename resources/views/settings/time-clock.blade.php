@extends('layouts.app')

@section('title', 'Kiosk Access')
@section('content')
    <div class="card" style="max-width:820px">
        <div class="card-head"><h2>Time clock — allowed networks</h2></div>
        <div class="card-body">
            <p class="muted small mb-0">Employees can only punch in from the IP addresses listed here. Leave the list empty to allow punching from anywhere (not recommended).</p>
            <p class="small" style="margin-top:10px">Your current IP: <strong>{{ $currentIp }}</strong></p>

            @if($ips->isEmpty())
                <div class="alert err">No rules yet — the kiosk currently accepts punches from any network.</div>
            @endif

            <form method="POST" action="{{ route('time-clock.ips.store') }}" class="filters" style="margin-bottom:6px">
                @csrf
                <div class="field" style="min-width:220px"><label>IP or CIDR range</label><input type="text" name="cidr" value="{{ old('cidr') }}" placeholder="192.168.1.0/24" required></div>
                <div class="field" style="min-width:180px"><label>Label</label><input type="text" name="label" value="{{ old('label') }}" placeholder="Main office"></div>
                <button class="btn primary">Add</button>
            </form>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>IP / range</th><th>Label</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse($ips as $ip)
                    <tr>
                        <td style="font-weight:600">{{ $ip->cidr }}</td>
                        <td class="muted">{{ $ip->label ?: '—' }}</td>
                        <td><span class="badge {{ $ip->is_active ? 'green' : 'gray' }}">{{ $ip->is_active ? 'Active' : 'Disabled' }}</span></td>
                        <td class="btn-row">
                            <form method="POST" action="{{ route('time-clock.ips.update', $ip) }}">@csrf @method('PUT')
                                <input type="hidden" name="is_active" value="{{ $ip->is_active ? 0 : 1 }}">
                                <button class="btn sm">{{ $ip->is_active ? 'Disable' : 'Enable' }}</button>
                            </form>
                            <form method="POST" action="{{ route('time-clock.ips.destroy', $ip) }}" onsubmit="return confirm('Remove this IP?')">@csrf @method('DELETE')<button class="btn sm danger">Remove</button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="muted">No allowed IPs yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
