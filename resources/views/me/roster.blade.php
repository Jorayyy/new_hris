@extends('layouts.app')

@section('title', 'My Roster')
@section('content')
    <div class="card">
        <div class="card-head">
            <h2>My Roster</h2>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Shift</th><th>Time in</th><th>Time out</th><th>From</th><th>To</th></tr></thead>
                <tbody>
                @forelse($assignments as $assignment)
                    <tr>
                        <td style="font-weight:600">{{ $assignment->shift?->name }}</td>
                        <td>{{ $assignment->shift?->time_in }}</td>
                        <td>{{ $assignment->shift?->time_out }}</td>
                        <td>{{ $assignment->effective_from?->format('M d, Y') }}</td>
                        <td>{{ $assignment->effective_to?->format('M d, Y') ?? 'Open' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">No published shifts for you yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
