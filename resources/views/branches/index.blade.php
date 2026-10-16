@extends('layouts.app')

@section('title', 'Branches')
@section('content')
    <div class="card">
        <div class="card-head">
            <h2>Branches</h2>
            <a class="btn primary sm" href="{{ route('branches.create') }}">+ New branch</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Name</th><th>Code</th><th>Address</th><th class="num">Employees</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse($branches as $branch)
                    <tr>
                        <td style="font-weight:600">{{ $branch->name }}</td>
                        <td>{{ $branch->code }}</td>
                        <td class="muted">{{ $branch->address ?? '—' }}</td>
                        <td class="num">{{ $branch->employees_count }}</td>
                        <td><span class="badge {{ $branch->is_active ? 'green' : 'gray' }}">{{ $branch->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td class="btn-row">
                            <a class="btn sm" href="{{ route('branches.edit', $branch) }}">Edit</a>
                            <form method="POST" action="{{ route('branches.destroy', $branch) }}" onsubmit="return confirm('Delete this branch?')">
                                @csrf @method('DELETE')
                                <button class="btn sm danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted">No branches yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $branches->links() }}
    </div>
@endsection
