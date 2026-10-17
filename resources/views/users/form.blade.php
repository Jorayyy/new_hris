@extends('layouts.app')

@section('title', $user->exists ? 'Edit user' : 'New user')
@section('content')
    <div class="card" style="max-width:680px">
        <div class="card-head"><h2>{{ $user->exists ? 'Edit user' : 'New user' }}</h2></div>
        <div class="card-body">
            <form method="POST" action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}">
                @csrf
                @if($user->exists) @method('PUT') @endif
                <div class="form-grid">
                    <div class="field">
                        <label>Name</label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" required>
                    </div>
                    <div class="field">
                        <label>Email</label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" required>
                    </div>
                    <div class="field">
                        <label>Password</label>
                        <input type="password" name="password" minlength="8" placeholder="{{ $user->exists ? 'Leave blank to keep current password' : '' }}" {{ $user->exists ? '' : 'required' }}>
                        <p class="muted">At least 8 characters, including a number.</p>
                    </div>
                    <div class="field">
                        <label>Role</label>
                        <select name="role" required>
                            <option value="{{ \App\Models\User::ROLE_SUPER_ADMIN }}" @selected(old('role', $user->role) === \App\Models\User::ROLE_SUPER_ADMIN)>Super admin</option>
                            <option value="{{ \App\Models\User::ROLE_ADMIN }}" @selected(old('role', $user->role) === \App\Models\User::ROLE_ADMIN)>Admin</option>
                            <option value="{{ \App\Models\User::ROLE_HR }}" @selected(old('role', $user->role) === \App\Models\User::ROLE_HR)>HR</option>
                            <option value="{{ \App\Models\User::ROLE_PAYROLL }}" @selected(old('role', $user->role) === \App\Models\User::ROLE_PAYROLL)>Payroll</option>
                            <option value="{{ \App\Models\User::ROLE_MANAGER }}" @selected(old('role', $user->role) === \App\Models\User::ROLE_MANAGER)>Manager</option>
                            <option value="{{ \App\Models\User::ROLE_EMPLOYEE }}" @selected(old('role', $user->role) === \App\Models\User::ROLE_EMPLOYEE)>Employee</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Branch</label>
                        <select name="branch_id">
                            <option value="">—</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" @selected(old('branch_id', $user->branch_id) == $branch->id)>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label>Linked employee</label>
                        <select name="employee_id">
                            <option value="">—</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}" @selected(old('employee_id', $user->employee?->id) == $employee->id)>{{ $employee->employee_no }} — {{ $employee->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field full">
                        <label style="display:flex;align-items:center;gap:8px;font-weight:500">
                            <input type="checkbox" name="is_active" value="1" style="width:auto" @checked(old('is_active', $user->is_active ?? true))> Active
                        </label>
                    </div>
                </div>
                <div class="btn-row">
                    <button class="btn primary">Save user</button>
                    <a class="btn" href="{{ route('users.index') }}">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
