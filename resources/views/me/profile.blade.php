@extends('layouts.app')

@section('title', 'My Profile')
@section('content')
    <div class="grid cols-2">
        <div class="card">
            <div class="card-head"><h2>Contact details</h2></div>
            <div class="card-body">
                @if($employee)
                    <form method="POST" action="{{ route('me.profile.update') }}">
                        @csrf @method('PUT')
                        <div class="field"><label>Employee</label><input type="text" value="{{ $employee->full_name }} ({{ $employee->employee_no }})" disabled></div>
                        <div class="field"><label>Contact number</label><input type="text" name="contact_no" value="{{ old('contact_no', $employee->contact_no) }}"></div>
                        <div class="field"><label>Personal email</label><input type="email" name="personal_email" value="{{ old('personal_email', $employee->personal_email) }}"></div>
                        <div class="field"><label>Address</label><input type="text" name="address" value="{{ old('address', $employee->address) }}"></div>
                        <button class="btn primary">Save changes</button>
                    </form>
                @else
                    <p class="muted">No employee record is linked to your account yet. Contact HR.</p>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-head"><h2>Change password</h2></div>
            <div class="card-body">
                <form method="POST" action="{{ route('me.password') }}">
                    @csrf @method('PUT')
                    <div class="field"><label>Current password</label><input type="password" name="current_password" required></div>
                    <div class="field"><label>New password</label><input type="password" name="password" required></div>
                    <div class="field"><label>Confirm new password</label><input type="password" name="password_confirmation" required></div>
                    <button class="btn primary">Update password</button>
                </form>
            </div>
        </div>
    </div>
@endsection
