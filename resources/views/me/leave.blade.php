@extends('layouts.app')

@section('title', 'My Leave')
@section('content')
    <div class="grid cols-3" style="margin-bottom:20px">
        @forelse($balances as $balance)
            <div class="stat">
                <div class="k">{{ $balance->leaveType?->name }}</div>
                <div class="v">{{ number_format($balance->remaining, 1) }}</div>
                <div class="s">{{ number_format($balance->used, 1) }} of {{ number_format($balance->credits, 1) }} days used</div>
            </div>
        @empty
            <div class="stat"><div class="k">Leave</div><div class="v">—</div><div class="s">No credits on file</div></div>
        @endforelse
    </div>

    <div class="grid cols-2">
        <div class="card">
            <div class="card-head"><h2>File a leave request</h2></div>
            <div class="card-body">
                <form method="POST" action="{{ route('me.leave.store') }}">
                    @csrf
                    <div class="field"><label>Leave type</label>
                        <select name="leave_type_id" required><option value="">Select type</option>
                            @foreach($leaveTypes as $type)<option value="{{ $type->id }}" @selected(old('leave_type_id') == $type->id)>{{ $type->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="form-grid">
                        <div class="field"><label>Date from</label><input type="date" name="date_from" value="{{ old('date_from') }}" required></div>
                        <div class="field"><label>Date to</label><input type="date" name="date_to" value="{{ old('date_to') }}" required></div>
                    </div>
                    <div class="field"><label>Reason</label><textarea name="reason" rows="3">{{ old('reason') }}</textarea></div>
                    <button class="btn primary">Submit</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-head"><h2>My requests</h2></div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Type</th><th>Dates</th><th class="num">Days</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse($requests as $leave)
                        <tr>
                            <td>{{ $leave->leaveType?->name }}</td>
                            <td class="small">{{ $leave->date_from->format('M d') }} – {{ $leave->date_to->format('M d, Y') }}</td>
                            <td class="num">{{ number_format($leave->days, 1) }}</td>
                            <td>
                                @php($tone = ['pending' => 'amber', 'approved' => 'green', 'rejected' => 'red', 'cancelled' => 'gray'][$leave->status] ?? 'gray')
                                <span class="badge {{ $tone }}">{{ ucfirst($leave->status) }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="muted">No requests yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
