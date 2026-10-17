@extends('layouts.app')

@section('title', 'Schedule Change')
@section('content')
    <div class="card">
        <div class="card-head">
            <h2>File a schedule change</h2>
        </div>
        <form method="POST" action="{{ route('me.schedule.store') }}" class="form-grid card-section">
            @csrf
            <div class="field"><label>Date</label><input type="date" name="work_date" value="{{ old('work_date', now()->toDateString()) }}" required></div>
            <div class="field"><label>Requested shift</label>
                <select name="shift_id" required>
                    <option value="">Select shift…</option>
                    @foreach($shifts as $shift)
                        <option value="{{ $shift->id }}" @selected(old('shift_id') == $shift->id)>{{ $shift->name }} ({{ $shift->time_in }}–{{ $shift->time_out }})</option>
                    @endforeach
                </select>
            </div>
            <div class="field full"><label>Reason</label><input name="reason" value="{{ old('reason') }}"></div>
            <div class="field full"><button class="btn primary">Submit request</button></div>
        </form>
    </div>

    <div class="card">
        <div class="card-head">
            <h2>My schedule change requests</h2>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Date</th><th>From</th><th>To</th><th>Reason</th><th>Status</th><th>Remarks</th></tr></thead>
                <tbody>
                @forelse($requests as $change)
                    <tr>
                        <td>{{ $change->work_date?->format('M d, Y') }}</td>
                        <td>{{ $change->currentShift?->name ?? '—' }}</td>
                        <td>{{ $change->shift?->name }}</td>
                        <td class="small">{{ $change->reason ?: '—' }}</td>
                        <td><span class="badge {{ $change->status === 'approved' ? 'green' : ($change->status === 'rejected' ? 'red' : 'amber') }}">{{ ucfirst($change->status) }}</span></td>
                        <td class="small muted">{{ $change->decision_remarks ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted">No schedule change requests yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
