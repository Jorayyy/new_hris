@extends('layouts.app')

@section('title', 'Schedule Changes')
@section('content')
    <div class="card">
        <div class="card-head">
            <h2>Schedule change requests</h2>
            <div class="btn-row">
                <a class="btn sm {{ $status === 'pending' ? 'primary' : '' }}" href="{{ route('schedule-changes.index', ['status' => 'pending']) }}">Pending</a>
                <a class="btn sm {{ $status === 'approved' ? 'primary' : '' }}" href="{{ route('schedule-changes.index', ['status' => 'approved']) }}">Approved</a>
                <a class="btn sm {{ $status === 'rejected' ? 'primary' : '' }}" href="{{ route('schedule-changes.index', ['status' => 'rejected']) }}">Rejected</a>
                <a class="btn sm {{ $status === 'all' ? 'primary' : '' }}" href="{{ route('schedule-changes.index', ['status' => 'all']) }}">All</a>
            </div>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Employee</th><th>Date</th><th>Change</th><th>Reason</th><th>Status</th><th>Review</th></tr></thead>
                <tbody>
                @forelse($requests as $change)
                    <tr>
                        <td style="font-weight:600">{{ $change->employee?->full_name }}<div class="small muted">{{ $change->employee?->employee_no }}</div></td>
                        <td>{{ $change->work_date?->format('M d, Y') }}</td>
                        <td>{{ $change->currentShift?->name ?? '—' }} → {{ $change->shift?->name }}</td>
                        <td class="small">{{ $change->reason ?: '—' }}</td>
                        <td>
                            <span class="badge {{ $change->status === 'approved' ? 'green' : ($change->status === 'rejected' ? 'red' : 'amber') }}">{{ ucfirst($change->status) }}</span>
                            @if($change->decider)<div class="small muted">{{ $change->decider->name }}</div>@endif
                        </td>
                        <td>
                            @if($change->status === 'pending')
                                <form method="POST" action="{{ route('schedule-changes.decide', $change) }}" class="filters" style="gap:6px">
                                    @csrf
                                    <input type="text" name="decision_remarks" placeholder="Remarks (optional)" style="width:150px">
                                    <button class="btn sm primary" name="decision" value="approve">Approve</button>
                                    <button class="btn sm danger" name="decision" value="reject">Reject</button>
                                </form>
                            @else
                                <span class="small muted">{{ $change->decision_remarks ?: '—' }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted">No {{ $status === 'all' ? '' : $status }} schedule change requests.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $requests->links() }}
    </div>
@endsection
