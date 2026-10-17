@extends('layouts.app')

@section('title', 'Overtime Filings')
@section('content')
    <div class="card">
        <div class="card-head">
            <h2>Overtime filings</h2>
            <div class="btn-row">
                <a class="btn sm {{ $status === 'pending' ? 'primary' : '' }}" href="{{ route('ot.index', ['status' => 'pending']) }}">Pending</a>
                <a class="btn sm {{ $status === 'approved' ? 'primary' : '' }}" href="{{ route('ot.index', ['status' => 'approved']) }}">Approved</a>
                <a class="btn sm {{ $status === 'rejected' ? 'primary' : '' }}" href="{{ route('ot.index', ['status' => 'rejected']) }}">Rejected</a>
                <a class="btn sm {{ $status === 'all' ? 'primary' : '' }}" href="{{ route('ot.index', ['status' => 'all']) }}">All</a>
            </div>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Employee</th><th>Date</th><th class="num">Hours</th><th>Reason</th><th>Status</th><th>Review</th></tr></thead>
                <tbody>
                @forelse($requests as $ot)
                    <tr>
                        <td style="font-weight:600">{{ $ot->employee?->full_name }}<div class="small muted">{{ $ot->employee?->employee_no }}</div></td>
                        <td>{{ $ot->work_date?->format('M d, Y') }}</td>
                        <td class="num">{{ hrs($ot->hours) }}</td>
                        <td class="small">{{ $ot->reason ?: '—' }}</td>
                        <td>
                            <span class="badge {{ $ot->status === 'approved' ? 'green' : ($ot->status === 'rejected' ? 'red' : 'amber') }}">{{ ucfirst($ot->status) }}</span>
                            @if($ot->decider)<div class="small muted">{{ $ot->decider->name }}</div>@endif
                        </td>
                        <td>
                            @if($ot->status === 'pending')
                                <form method="POST" action="{{ route('ot.decide', $ot) }}" class="filters" style="gap:6px">
                                    @csrf
                                    <input type="text" name="decision_remarks" placeholder="Remarks (optional)" style="width:150px">
                                    <button class="btn sm primary" name="decision" value="approve">Approve</button>
                                    <button class="btn sm danger" name="decision" value="reject">Reject</button>
                                </form>
                            @else
                                <span class="small muted">{{ $ot->decision_remarks ?: '—' }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted">No {{ $status === 'all' ? '' : $status }} overtime filings.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $requests->links() }}
    </div>
@endsection
