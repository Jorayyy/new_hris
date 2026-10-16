@extends('layouts.app')

@section('title', 'Attendance Corrections')
@section('content')
    @php $show = fn ($value) => $value?->format('h:i A') ?? '—'; @endphp
    <div class="card">
        <div class="card-head">
            <h2>Punch correction requests</h2>
            <div class="btn-row">
                <a class="btn sm {{ $status === 'pending' ? 'primary' : '' }}" href="{{ route('attendance.corrections.index', ['status' => 'pending']) }}">Pending</a>
                <a class="btn sm {{ $status === 'approved' ? 'primary' : '' }}" href="{{ route('attendance.corrections.index', ['status' => 'approved']) }}">Approved</a>
                <a class="btn sm {{ $status === 'rejected' ? 'primary' : '' }}" href="{{ route('attendance.corrections.index', ['status' => 'rejected']) }}">Rejected</a>
                <a class="btn sm {{ $status === 'all' ? 'primary' : '' }}" href="{{ route('attendance.corrections.index', ['status' => 'all']) }}">All</a>
            </div>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Employee</th><th>Date</th><th>Proposed punches</th><th>Reason</th><th>Status</th><th>Review</th></tr></thead>
                <tbody>
                @forelse($corrections as $c)
                    <tr>
                        <td style="font-weight:600">{{ $c->employee?->full_name }}<div class="small muted">{{ $c->employee?->employee_no }}</div></td>
                        <td>{{ $c->work_date->format('M d, Y') }}</td>
                        <td>
                            <div>{{ $show($c->time_in) }} → {{ $show($c->time_out) }}</div>
                            <div class="small muted">B1 {{ $show($c->break1_out) }}/{{ $show($c->break1_in) }} · L {{ $show($c->lunch_out) }}/{{ $show($c->lunch_in) }} · B2 {{ $show($c->break2_out) }}/{{ $show($c->break2_in) }}</div>
                        </td>
                        <td class="small">{{ $c->reason }}</td>
                        <td>
                            <span class="badge {{ $c->status === 'approved' ? 'green' : ($c->status === 'rejected' ? 'red' : 'amber') }}">{{ ucfirst($c->status) }}</span>
                            @if($c->decider)<div class="small muted">{{ $c->decider->name }}</div>@endif
                        </td>
                        <td>
                            @if($c->status === 'pending')
                                <form method="POST" action="{{ route('attendance.corrections.decide', $c) }}" class="filters" style="gap:6px">
                                    @csrf
                                    <input type="text" name="decision_remarks" placeholder="Remarks (optional)" style="width:150px">
                                    <button class="btn sm primary" name="decision" value="approve">Approve</button>
                                    <button class="btn sm danger" name="decision" value="reject">Reject</button>
                                </form>
                            @else
                                <span class="small muted">{{ $c->decision_remarks ?: '—' }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted">No {{ $status === 'all' ? '' : $status }} correction requests.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $corrections->links() }}
    </div>
@endsection
