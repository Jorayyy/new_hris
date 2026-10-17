@extends('layouts.app')

@section('title', 'Overtime')
@section('content')
    <div class="card">
        <div class="card-head">
            <h2>File overtime</h2>
        </div>
        <form method="POST" action="{{ route('me.ot.store') }}" class="form-grid" style="padding:16px">
            @csrf
            <div class="field"><label>Date</label><input type="date" name="work_date" value="{{ old('work_date', now()->toDateString()) }}" required></div>
            <div class="field"><label>Hours</label><input type="number" step="0.25" min="0.5" max="12" name="hours" value="{{ old('hours') }}" required></div>
            <div class="field full"><label>Reason</label><input name="reason" value="{{ old('reason') }}"></div>
            <div class="field full"><button class="btn primary">Submit filing</button></div>
        </form>
    </div>

    <div class="card">
        <div class="card-head">
            <h2>My overtime filings</h2>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Date</th><th class="num">Hours</th><th>Reason</th><th>Status</th><th>Remarks</th></tr></thead>
                <tbody>
                @forelse($requests as $ot)
                    <tr>
                        <td>{{ $ot->work_date?->format('M d, Y') }}</td>
                        <td class="num">{{ hrs($ot->hours) }}</td>
                        <td class="small">{{ $ot->reason ?: '—' }}</td>
                        <td><span class="badge {{ $ot->status === 'approved' ? 'green' : ($ot->status === 'rejected' ? 'red' : 'amber') }}">{{ ucfirst($ot->status) }}</span></td>
                        <td class="small muted">{{ $ot->decision_remarks ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">No overtime filings yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
