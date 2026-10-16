@extends('layouts.app')

@section('title', 'Leave Requests')
@section('content')
    <div class="card">
        <div class="card-head"><h2>File a leave request</h2></div>
        <div class="card-body">
            <form method="POST" action="{{ route('leave-requests.store') }}">
                @csrf
                <div class="form-grid">
                    <div class="field"><label>Employee</label>
                        <select name="employee_id" required><option value="">Select employee</option>
                            @foreach($employees as $employee)<option value="{{ $employee->id }}" @selected(old('employee_id') == $employee->id)>{{ $employee->full_name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="field"><label>Leave type</label>
                        <select name="leave_type_id" required><option value="">Select type</option>
                            @foreach($leaveTypes as $type)<option value="{{ $type->id }}" @selected(old('leave_type_id') == $type->id)>{{ $type->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="field"><label>Date from</label><input type="date" name="date_from" value="{{ old('date_from') }}" required></div>
                    <div class="field"><label>Date to</label><input type="date" name="date_to" value="{{ old('date_to') }}" required></div>
                    <div class="field full"><label>Reason</label><textarea name="reason" rows="2">{{ old('reason') }}</textarea></div>
                </div>
                <div class="btn-row"><button class="btn primary">Submit request</button></div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-head">
            <h2>All requests</h2>
            <form method="GET" class="filters">
                <div class="field"><label>Status</label>
                    <select name="status" onchange="this.form.submit()">
                        <option value="">All</option>
                        @foreach(['pending','approved','rejected','cancelled'] as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Employee</th><th>Type</th><th>From</th><th>To</th><th class="num">Days</th><th>Paid</th><th>Status</th><th>Decide</th></tr></thead>
                <tbody>
                @forelse($requests as $leave)
                    <tr>
                        <td style="font-weight:600">{{ $leave->employee?->full_name }}</td>
                        <td>{{ $leave->leaveType?->name }}</td>
                        <td>{{ $leave->date_from->format('M d, Y') }}</td>
                        <td>{{ $leave->date_to->format('M d, Y') }}</td>
                        <td class="num">{{ number_format($leave->days, 2) }}</td>
                        <td>{!! $leave->is_paid ? '<span class="badge green">Paid</span>' : '<span class="badge gray">Unpaid</span>' !!}</td>
                        <td>
                            @php($tone = ['pending' => 'amber', 'approved' => 'green', 'rejected' => 'red', 'cancelled' => 'gray'][$leave->status] ?? 'gray')
                            <span class="badge {{ $tone }}">{{ ucfirst($leave->status) }}</span>
                        </td>
                        <td>
                            @if($leave->status === 'pending')
                                <div class="btn-row">
                                    <form method="POST" action="{{ route('leave-requests.decide', $leave) }}">@csrf<input type="hidden" name="status" value="approved"><button class="btn sm primary">Approve</button></form>
                                    <form method="POST" action="{{ route('leave-requests.decide', $leave) }}">@csrf<input type="hidden" name="status" value="rejected"><button class="btn sm danger">Reject</button></form>
                                </div>
                            @else
                                <span class="muted small">{{ $leave->approver?->name ?? '—' }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="muted">No leave requests.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $requests->links() }}
    </div>
@endsection
