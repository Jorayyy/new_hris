@extends('layouts.app')

@section('title', 'My DTR')
@section('content')
    @php
        $t = fn ($value) => $value?->format('h:i A') ?? '—';
        $pair = fn ($out, $in) => $t($out).' / '.$t($in);
    @endphp

    <div class="grid cols-4" style="margin-bottom:18px">
        <div class="stat"><div class="k">Days present</div><div class="v">{{ $totals['present'] }}</div><div class="s">{{ $from->format('M d') }} – {{ $to->format('M d, Y') }}</div></div>
        <div class="stat"><div class="k">Hours worked</div><div class="v">{{ hrs($totals['hours']) }}</div><div class="s">Late {{ mins($totals['late']) }} · UT {{ mins($totals['undertime']) }}</div></div>
        <div class="stat"><div class="k">Overtime</div><div class="v">{{ hrs($totals['overtime']) }}</div><div class="s">hours</div></div>
        <div class="stat"><div class="k">Night differential</div><div class="v">{{ hrs($totals['night']) }}</div><div class="s">hours (10pm–6am)</div></div>
    </div>

    <div class="card">
        <div class="card-head">
            <h2>My Daily Time Records</h2>
            <form method="GET" class="filters">
                <div class="field"><label>From</label><input type="date" name="from" value="{{ $from->format('Y-m-d') }}"></div>
                <div class="field"><label>To</label><input type="date" name="to" value="{{ $to->format('Y-m-d') }}"></div>
                <button class="btn primary">View</button>
                <a class="btn" href="{{ route('me.tk') }}">Fix a punch</a>
            </form>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr>
                    <th>Date</th><th>Shift</th><th>In</th><th>Break 1</th><th>Lunch</th><th>Break 2</th><th>Out</th>
                    <th class="num">Hours</th><th class="num">Late</th><th class="num">UT</th><th class="num">OT</th><th class="num">ND</th><th>Status</th><th></th>
                </tr></thead>
                <tbody>
                @forelse($attendances as $row)
                    <tr>
                        <td>{{ $row->work_date->format('D, M d') }}</td>
                        <td class="small muted">{{ $row->shift?->name ?? '—' }}</td>
                        <td>{{ $t($row->time_in) }}</td>
                        <td>{{ $pair($row->break1_out, $row->break1_in) }}</td>
                        <td>{{ $pair($row->lunch_out, $row->lunch_in) }}</td>
                        <td>{{ $pair($row->break2_out, $row->break2_in) }}</td>
                        <td>{{ $t($row->time_out) }}</td>
                        <td class="num">{{ hrs($row->hours_worked) }}</td>
                        <td class="num">{{ $row->late_minutes ? mins($row->late_minutes) : '—' }}</td>
                        <td class="num">{{ $row->undertime_minutes ? mins($row->undertime_minutes) : '—' }}</td>
                        <td class="num">{{ hrs($row->overtime_hours) }}</td>
                        <td class="num">{{ hrs($row->night_diff_hours) }}</td>
                        <td><span class="badge {{ $row->status === 'present' ? 'green' : ($row->status === 'absent' ? 'red' : 'blue') }}">{{ ucfirst($row->status) }}</span></td>
                        <td><a class="btn sm ghost" href="{{ route('me.tk', ['date' => $row->work_date->format('Y-m-d')]) }}">Fix</a></td>
                    </tr>
                @empty
                    <tr><td colspan="14" class="muted">No records in this range.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
