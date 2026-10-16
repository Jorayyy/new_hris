@extends('layouts.app')

@section('title', 'Departments')
@section('content')
    <div class="card">
        <div class="card-head">
            <h2>Departments</h2>
            <a class="btn primary sm" href="{{ route('departments.create') }}">+ New department</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Name</th><th>Branch</th><th>Code</th><th class="num">Employees</th><th></th></tr></thead>
                <tbody>
                @forelse($departments as $department)
                    <tr>
                        <td style="font-weight:600">{{ $department->name }}</td>
                        <td>{{ $department->branch?->name ?? 'All' }}</td>
                        <td>{{ $department->code ?? '—' }}</td>
                        <td class="num">{{ $department->employees_count }}</td>
                        <td class="btn-row">
                            <a class="btn sm" href="{{ route('departments.edit', $department) }}">Edit</a>
                            <form method="POST" action="{{ route('departments.destroy', $department) }}" onsubmit="return confirm('Delete this department?')">
                                @csrf @method('DELETE')
                                <button class="btn sm danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">No departments yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $departments->links() }}
    </div>
@endsection
