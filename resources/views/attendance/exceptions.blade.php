@extends('layouts.app')

@section('title', 'Attendance Exceptions')
@section('content')
    <div class="grid cols-4" style="margin-bottom:18px">
        <div class="stat"><div class="k">Late</div><div class="v">{{ $totals['late'] }}</div><div class="s">records</div></div>
        <div class="stat"><div class="k">Undertime</div><div class="v">{{ $totals['undertime'] }}</div><div class="s">records</div></div>
        <div class="stat"><div class="k">Absent</div><div class="v">{{ $totals['absent'] }}</div><div class="s">records</div></div>
        <div class="stat"><div class="k">Incomplete</div><div class="v">{{ $totals['incomplete'] }}</div><div class="s">missing time out</div></div>
    </div>

    <div class="card">
        <div class="card-head">
            <h2>Exception report</h2>
            <div class="btn-row">
                @foreach(['all' => 'All', 'late' => 'Late', 'undertime' => 'Undertime', 'absent' => 'Absent', 'incomplete' => 'Incomplete'] as $key => $label)
                    <a class="btn sm {{ $type === $key ? 'primary' : '' }}" href="{{ route('attendance.exceptions.index', array_merge(request()->query(), ['type' => $key])) }}">{{ $label }}</a>
                @endforeach
            </div>
        </div>
        <div class="card-body" style="padding-bottom:0">
            <form method="GET" class="filters">
                <input type="hidden" name="type" value="{{ $type }}">
                <div class="field"><label>From</label><input type="date" name="from" value="{{ $from->format('Y-m-d') }}"></div>
                <div class="field"><label>To</label><input type="date" name="to" value="{{ $to->format('Y-m-d') }}"></div>
                <div class="field" style="min-width:200px"><label>Employee</label>
                    <select name="employee"><option value="">All</option>
                        @foreach($employees as $employee)<option value="{{ $employee->id }}" @selected(request('employee') == $employee->id)>{{ $employee->full_name }}</option>@endforeach
                    </select>
                </div>
                <div class="field"><label>Branch</label>
                    <select name="branch"><option value="">All</option>
                        @foreach($branches as $branch)<option value="{{ $branch->id }}" @selected(request('branch') == $branch->id)>{{ $branch->name }}</option>@endforeach
                    </select>
                </div>
                <button class="btn primary">Filter</button>
                <a class="btn" href="{{ route('attendance.exceptions.index') }}">Reset</a>
            </form>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Date</th><th>Employee</th><th>Branch</th><th>In</th><th>Out</th><th class="num">Late</th><th class="num">UT</th><th>Exceptions</th></tr></thead>
                <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td>{{ $row->work_date->format('D, M d') }}</td>
                        <td style="font-weight:600">{{ $row->employee?->full_name }}<div class="small muted">{{ $row->employee?->employee_no }}</div></td>
                        <td>{{ $row->branch?->name ?? '—' }}</td>
                        <td>{{ $row->time_in?->format('h:i A') ?? '—' }}</td>
                        <td>{{ $row->time_out?->format('h:i A') ?? '—' }}</td>
                        <td class="num">{{ $row->late_minutes ? mins($row->late_minutes) : '—' }}</td>
                        <td class="num">{{ $row->undertime_minutes ? mins($row->undertime_minutes) : '—' }}</td>
                        <td class="btn-row">
                            @if($row->status === 'absent')<span class="badge red">Absent</span>@endif
                            @if($row->late_minutes > 0)<span class="badge amber">Late</span>@endif
                            @if($row->undertime_minutes > 0)<span class="badge amber">Undertime</span>@endif
                            @if($row->time_in && ! $row->time_out)<span class="badge gray">Missing out</span>@endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="muted">No exceptions in this range.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $rows->links() }}
    </div>
@endsection
