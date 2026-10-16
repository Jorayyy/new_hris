@extends('layouts.app')

@section('title', 'Holidays')
@section('content')
    <div class="card">
        <div class="card-head">
            <h2>Holidays</h2>
            <a class="btn primary sm" href="{{ route('holidays.create') }}">+ New holiday</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Date</th><th>Name</th><th>Type</th><th>Branch</th><th class="num">Multiplier</th><th></th></tr></thead>
                <tbody>
                @forelse($holidays as $holiday)
                    <tr>
                        <td>{{ $holiday->date?->format('M d, Y') }}</td>
                        <td style="font-weight:600">{{ $holiday->name }}</td>
                        <td><span class="badge {{ $holiday->type === 'regular' ? 'blue' : 'amber' }}">{{ ucfirst($holiday->type) }}</span></td>
                        <td class="muted">{{ $holiday->branch?->name ?? 'All branches' }}</td>
                        <td class="num">{{ $holiday->rate_multiplier }}×</td>
                        <td class="btn-row">
                            <a class="btn sm" href="{{ route('holidays.edit', $holiday) }}">Edit</a>
                            <form method="POST" action="{{ route('holidays.destroy', $holiday) }}" onsubmit="return confirm('Delete this holiday?')">
                                @csrf @method('DELETE')
                                <button class="btn sm danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted">No holidays yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $holidays->links() }}
    </div>
@endsection
