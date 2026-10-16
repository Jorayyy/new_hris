@extends('layouts.app')

@section('title', 'Positions')
@section('content')
    <div class="card">
        <div class="card-head">
            <h2>Positions</h2>
            <a class="btn primary sm" href="{{ route('positions.create') }}">+ New position</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Title</th><th>Department</th><th class="num">Default salary</th><th class="num">Employees</th><th></th></tr></thead>
                <tbody>
                @forelse($positions as $position)
                    <tr>
                        <td style="font-weight:600">{{ $position->title }}</td>
                        <td>{{ $position->department?->name ?? '—' }}</td>
                        <td class="num">{{ $position->default_salary ? peso($position->default_salary) : '—' }}</td>
                        <td class="num">{{ $position->employees_count }}</td>
                        <td class="btn-row">
                            <a class="btn sm" href="{{ route('positions.edit', $position) }}">Edit</a>
                            <form method="POST" action="{{ route('positions.destroy', $position) }}" onsubmit="return confirm('Delete this position?')">
                                @csrf @method('DELETE')
                                <button class="btn sm danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">No positions yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $positions->links() }}
    </div>
@endsection
