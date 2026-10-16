@extends('layouts.app')

@section('title', 'User Accounts')
@section('content')
    @php($roleColors = [
        \App\Models\User::ROLE_SUPER_ADMIN => 'red',
        \App\Models\User::ROLE_ADMIN => 'blue',
        \App\Models\User::ROLE_HR => 'cyan',
        \App\Models\User::ROLE_PAYROLL => 'amber',
        \App\Models\User::ROLE_MANAGER => 'green',
    ])
    <div class="card">
        <div class="card-head">
            <h2>User Accounts</h2>
            <a class="btn primary sm" href="{{ route('users.create') }}">+ New user</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Branch</th><th>Linked employee</th><th>Active</th><th></th></tr></thead>
                <tbody>
                @forelse($users as $user)
                    <tr>
                        <td style="font-weight:600">{{ $user->name }}</td>
                        <td class="muted">{{ $user->email }}</td>
                        <td><span class="badge {{ $roleColors[$user->role] ?? 'gray' }}">{{ $user->role_label }}</span></td>
                        <td class="muted">{{ $user->branch?->name ?? '—' }}</td>
                        <td class="muted">{{ $user->employee?->full_name ?? '—' }}</td>
                        <td><span class="badge {{ $user->is_active ? 'green' : 'gray' }}">{{ $user->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td class="btn-row">
                            <a class="btn sm" href="{{ route('users.edit', $user) }}">Edit</a>
                            <form method="POST" action="{{ route('users.destroy', $user) }}" onsubmit="return confirm('Delete this user?')">
                                @csrf @method('DELETE')
                                <button class="btn sm danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="muted">No users yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $users->links() }}
    </div>
@endsection
