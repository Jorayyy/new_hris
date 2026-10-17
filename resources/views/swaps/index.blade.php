@extends('layouts.app')

@section('title', 'Shift Swaps')
@section('content')
    <div class="card">
        <div class="card-head">
            <h2>Shift swap requests</h2>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Requester</th><th>With</th><th>Date</th><th>Requested</th><th>Reason</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse($requests as $swap)
                    <tr>
                        <td style="font-weight:600">{{ $swap->requester?->full_name }}</td>
                        <td>{{ $swap->target?->full_name }}</td>
                        <td>{{ $swap->swap_date?->format('M d, Y') }}</td>
                        <td>{{ $swap->requesterShift?->name }} ⇄ {{ $swap->targetShift?->name }}</td>
                        <td>{{ $swap->reason ?: '—' }}</td>
                        <td><span class="badge {{ $swap->status === 'approved' ? 'green' : ($swap->status === 'rejected' ? 'red' : 'gray') }}">{{ ucfirst($swap->status) }}</span></td>
                        <td class="btn-row">
                            @if($swap->status === 'pending')
                                <form method="POST" action="{{ route('swaps.decide', $swap) }}">
                                    @csrf
                                    <input type="hidden" name="decision" value="approve">
                                    <button class="btn sm primary">Approve</button>
                                </form>
                                <form method="POST" action="{{ route('swaps.decide', $swap) }}">
                                    @csrf
                                    <input type="hidden" name="decision" value="reject">
                                    <button class="btn sm danger">Reject</button>
                                </form>
                            @else
                                <span class="small">{{ $swap->decider?->name }} · {{ $swap->decided_at?->format('M d, Y') }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="muted">No swap requests yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $requests->links() }}
    </div>
@endsection
