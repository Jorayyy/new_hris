@extends('layouts.app')

@section('title', 'Roster')
@section('content')
    <div class="card">
        <div class="card-head">
            <h2>Assign shift</h2>
        </div>
        <form method="POST" action="{{ route('roster.store') }}" class="form-grid card-section">
            @csrf
            <div class="field"><label>Employee</label>
                <select name="employee_id" required>
                    <option value="">Select employee…</option>
                    @foreach($employees as $e)
                        <option value="{{ $e->id }}" @selected(old('employee_id') == $e->id)>{{ $e->full_name }} ({{ $e->employee_no }})</option>
                    @endforeach
                </select>
            </div>
            <div class="field"><label>Shift</label>
                <select name="shift_id" required>
                    <option value="">Select shift…</option>
                    @foreach($shifts as $shift)
                        <option value="{{ $shift->id }}" @selected(old('shift_id') == $shift->id)>{{ $shift->name }} ({{ $shift->time_in }}–{{ $shift->time_out }})</option>
                    @endforeach
                </select>
            </div>
            <div class="field"><label>Effective from</label><input type="date" name="effective_from" value="{{ old('effective_from', now()->toDateString()) }}" required></div>
            <div class="field"><label>Effective to (optional)</label><input type="date" name="effective_to" value="{{ old('effective_to') }}"></div>
            <div class="field full"><button class="btn primary">Assign shift</button></div>
        </form>
    </div>

    <div class="card">
        <div class="card-head">
            <h2>Roster</h2>
            <form method="POST" action="{{ route('roster.publish') }}" onsubmit="return confirm('Publish the roster to employees?')">
                @csrf
                <button class="btn sm primary" @disabled($pending === 0)>Publish{{ $pending ? ' ('.$pending.')' : '' }}</button>
            </form>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Employee</th><th>Shift</th><th>From</th><th>To</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse($assignments as $assignment)
                    <tr>
                        <td style="font-weight:600">{{ $assignment->employee?->full_name }}</td>
                        <td>{{ $assignment->shift?->name }}</td>
                        <td>{{ $assignment->effective_from?->format('M d, Y') }}</td>
                        <td>{{ $assignment->effective_to?->format('M d, Y') ?? 'Open' }}</td>
                        <td><span class="badge {{ $assignment->published_at ? 'green' : 'gray' }}">{{ $assignment->published_at ? 'Published' : 'Draft' }}</span></td>
                        <td class="btn-row">
                            <form method="POST" action="{{ route('roster.destroy', $assignment) }}" onsubmit="return confirm('Remove this shift assignment?')">
                                @csrf @method('DELETE')
                                <button class="btn sm danger">Remove</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted">No shift assignments yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $assignments->links() }}
    </div>
@endsection
