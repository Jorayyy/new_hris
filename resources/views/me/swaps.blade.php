@extends('layouts.app')

@section('title', 'Shift Swaps')
@section('content')
    <div class="card">
        <div class="card-head">
            <h2>Request a shift swap</h2>
        </div>
        <form method="POST" action="{{ route('me.swaps.store') }}" class="form-grid" style="padding:16px">
            @csrf
            <div class="field"><label>Swap with</label>
                <select name="target_id" required>
                    <option value="">Select colleague…</option>
                    @foreach($colleagues as $c)
                        <option value="{{ $c->id }}" @selected(old('target_id') == $c->id)>{{ $c->full_name }} ({{ $c->employee_no }})</option>
                    @endforeach
                </select>
            </div>
            <div class="field"><label>Date</label><input type="date" name="swap_date" value="{{ old('swap_date', now()->toDateString()) }}" required></div>
            <div class="field full"><label>Reason (optional)</label><input name="reason" value="{{ old('reason') }}"></div>
            <div class="field full"><button class="btn primary">Request swap</button></div>
        </form>
    </div>

    <div class="card">
        <div class="card-head">
            <h2>My swap requests</h2>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>With</th><th>Date</th><th>My shift</th><th>Their shift</th><th>Status</th></tr></thead>
                <tbody>
                @forelse($requests as $swap)
                    <tr>
                        <td style="font-weight:600">{{ $swap->target?->full_name }}</td>
                        <td>{{ $swap->swap_date?->format('M d, Y') }}</td>
                        <td>{{ $swap->requesterShift?->name }}</td>
                        <td>{{ $swap->targetShift?->name }}</td>
                        <td><span class="badge {{ $swap->status === 'approved' ? 'green' : ($swap->status === 'rejected' ? 'red' : 'gray') }}">{{ ucfirst($swap->status) }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">No swap requests yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
