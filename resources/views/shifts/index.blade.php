@extends('layouts.app')

@section('title', 'Shifts')
@section('content')
    <div class="card">
        <div class="card-head">
            <h2>Shifts</h2>
            <a class="btn primary sm" href="{{ route('shifts.create') }}">+ New shift</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Name</th><th>Time in</th><th>Time out</th><th class="num">Break</th><th class="num">Grace</th><th class="num">Required hrs</th><th>Night diff</th><th class="num">Employees</th><th></th></tr></thead>
                <tbody>
                @forelse($shifts as $shift)
                    <tr>
                        <td style="font-weight:600">{{ $shift->name }}</td>
                        <td>{{ $shift->time_in }}</td>
                        <td>{{ $shift->time_out }}</td>
                        <td class="num">{{ $shift->break_minutes }}m</td>
                        <td class="num">{{ $shift->grace_minutes }}m</td>
                        <td class="num">{{ hrs($shift->required_hours) }}</td>
                        <td><span class="badge {{ $shift->is_night_diff ? 'cyan' : 'gray' }}">{{ $shift->is_night_diff ? 'Yes' : 'No' }}</span></td>
                        <td class="num">{{ $shift->employee_shifts_count }}</td>
                        <td class="btn-row">
                            <a class="btn sm" href="{{ route('shifts.edit', $shift) }}">Edit</a>
                            <form method="POST" action="{{ route('shifts.destroy', $shift) }}" onsubmit="return confirm('Delete this shift?')">
                                @csrf @method('DELETE')
                                <button class="btn sm danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="muted">No shifts yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $shifts->links() }}
    </div>
@endsection
