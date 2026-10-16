@extends('layouts.app')

@section('title', 'My DTR')
@section('content')
    <div class="card">
        <div class="card-head">
            <h2>My Daily Time Records</h2>
            <form method="GET" class="filters">
                <div class="field"><label>From</label><input type="date" name="from" value="{{ $from->format('Y-m-d') }}"></div>
                <div class="field"><label>To</label><input type="date" name="to" value="{{ $to->format('Y-m-d') }}"></div>
                <button class="btn primary">View</button>
            </form>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Date</th><th>In</th><th>Out</th><th class="num">Hours</th><th class="num">Late</th><th class="num">UT</th><th class="num">OT</th><th class="num">ND</th><th>Status</th></tr></thead>
                <tbody>
                @forelse($attendances as $row)
                    <tr>
                        <td>{{ $row->work_date->format('M d, Y') }}</td>
                        <td>{{ $row->time_in?->format('h:i A') ?? '—' }}</td>
                        <td>{{ $row->time_out?->format('h:i A') ?? '—' }}</td>
                        <td class="num">{{ hrs($row->hours_worked) }}</td>
                        <td class="num">{{ $row->late_minutes ?: '—' }}</td>
                        <td class="num">{{ $row->undertime_minutes ?: '—' }}</td>
                        <td class="num">{{ hrs($row->overtime_hours) }}</td>
                        <td class="num">{{ hrs($row->night_diff_hours) }}</td>
                        <td><span class="badge {{ $row->status === 'present' ? 'green' : ($row->status === 'absent' ? 'red' : 'blue') }}">{{ ucfirst($row->status) }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="muted">No records in this range.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
