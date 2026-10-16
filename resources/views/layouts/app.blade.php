<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('app.css') }}">
</head>
<body>
@php($u = auth()->user())
<div class="layout">
    <aside class="sidebar">
        <div class="brand"><span class="dot"></span> {{ config('app.name') }}</div>

        <div class="nav-group">
            <div class="label">Overview</div>
            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}"><span class="ico">▦</span> Dashboard</a>
        </div>

        <div class="nav-group">
            <div class="label">My Space</div>
            <a href="{{ route('me.dtr') }}" class="{{ request()->routeIs('me.dtr') ? 'active' : '' }}"><span class="ico">⏱</span> My DTR</a>
            <a href="{{ route('me.payslips') }}" class="{{ request()->routeIs('me.payslips*') ? 'active' : '' }}"><span class="ico">₱</span> My Payslips</a>
            <a href="{{ route('me.leave') }}" class="{{ request()->routeIs('me.leave') ? 'active' : '' }}"><span class="ico">☖</span> My Leave</a>
            <a href="{{ route('me.loans') }}" class="{{ request()->routeIs('me.loans') ? 'active' : '' }}"><span class="ico">◷</span> My Loans</a>
        </div>

        @if($u->hasRole(\App\Models\User::HR_ROLES))
            <div class="nav-group">
                <div class="label">People</div>
                <a href="{{ route('employees.index') }}" class="{{ request()->routeIs('employees.*') ? 'active' : '' }}"><span class="ico">☺</span> Employees</a>
                <a href="{{ route('announcements.index') }}" class="{{ request()->routeIs('announcements.*') ? 'active' : '' }}"><span class="ico">✦</span> Announcements</a>
            </div>
            <div class="nav-group">
                <div class="label">Organization</div>
                <a href="{{ route('branches.index') }}" class="{{ request()->routeIs('branches.*') ? 'active' : '' }}"><span class="ico">⌂</span> Branches</a>
                <a href="{{ route('departments.index') }}" class="{{ request()->routeIs('departments.*') ? 'active' : '' }}"><span class="ico">▤</span> Departments</a>
                <a href="{{ route('positions.index') }}" class="{{ request()->routeIs('positions.*') ? 'active' : '' }}"><span class="ico">★</span> Positions</a>
            </div>
        @endif

        @if($u->hasRole(\App\Models\User::MANAGEMENT_ROLES))
            <div class="nav-group">
                <div class="label">Time &amp; Attendance</div>
                <a href="{{ route('attendance.index') }}" class="{{ request()->routeIs('attendance.index', 'attendance.edit') ? 'active' : '' }}"><span class="ico">⏱</span> Attendance / DTR</a>
                <a href="{{ route('attendance.import.form') }}" class="{{ request()->routeIs('attendance.import*') ? 'active' : '' }}"><span class="ico">↥</span> Import Biometrics</a>
                <a href="{{ route('shifts.index') }}" class="{{ request()->routeIs('shifts.*') ? 'active' : '' }}"><span class="ico">◔</span> Shifts</a>
                <a href="{{ route('holidays.index') }}" class="{{ request()->routeIs('holidays.*') ? 'active' : '' }}"><span class="ico">✷</span> Holidays</a>
            </div>
            <div class="nav-group">
                <div class="label">Leave</div>
                <a href="{{ route('leave-requests.index') }}" class="{{ request()->routeIs('leave-requests.*') ? 'active' : '' }}"><span class="ico">☖</span> Leave Requests</a>
                @if($u->hasRole(\App\Models\User::PAYROLL_ROLES))
                    <a href="{{ route('leave-types.index') }}" class="{{ request()->routeIs('leave-types.*') ? 'active' : '' }}"><span class="ico">≡</span> Leave Types</a>
                @endif
            </div>
            <div class="nav-group">
                <div class="label">Loans</div>
                <a href="{{ route('loans.index') }}" class="{{ request()->routeIs('loans.*') ? 'active' : '' }}"><span class="ico">◷</span> Employee Loans</a>
                @if($u->hasRole(\App\Models\User::PAYROLL_ROLES))
                    <a href="{{ route('loan-types.index') }}" class="{{ request()->routeIs('loan-types.*') ? 'active' : '' }}"><span class="ico">≡</span> Loan Types</a>
                @endif
            </div>
            <div class="nav-group">
                <div class="label">Reports</div>
                <a href="{{ route('government.index') }}" class="{{ request()->routeIs('government.index') ? 'active' : '' }}"><span class="ico">▦</span> Gov't Tables</a>
                <a href="{{ route('government.remittances') }}" class="{{ request()->routeIs('government.remittances') ? 'active' : '' }}"><span class="ico">⇪</span> Remittances</a>
            </div>
        @endif

        @if($u->hasRole(\App\Models\User::PAYROLL_ROLES))
            <div class="nav-group">
                <div class="label">Payroll</div>
                <a href="{{ route('payroll.periods.index') }}" class="{{ request()->routeIs('payroll.periods.*') ? 'active' : '' }}"><span class="ico">▦</span> Pay Periods</a>
                <a href="{{ route('payroll.runs.index') }}" class="{{ request()->routeIs('payroll.runs.*') ? 'active' : '' }}"><span class="ico">▷</span> Payroll Runs</a>
                <a href="{{ route('payroll.thirteenth') }}" class="{{ request()->routeIs('payroll.thirteenth') ? 'active' : '' }}"><span class="ico">13</span> 13th Month</a>
            </div>
        @endif

        @if($u->hasRole([\App\Models\User::ROLE_SUPER_ADMIN, \App\Models\User::ROLE_ADMIN]))
            <div class="nav-group">
                <div class="label">Admin</div>
                <a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.*') ? 'active' : '' }}"><span class="ico">⚙</span> User Accounts</a>
            </div>
        @endif

        <div style="flex:1"></div>
        <div class="nav-group" style="padding-bottom:18px">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn" style="width:100%;justify-content:center;background:var(--sidebar-2);border-color:transparent;color:var(--sidebar-ink-strong)">Sign out</button>
            </form>
        </div>
    </aside>

    <div class="main">
        <header class="topbar">
            <h1>@yield('title', 'Dashboard')</h1>
            <div class="who">
                <div style="text-align:right">
                    <div style="font-weight:600;color:var(--text)">{{ $u->name }}</div>
                    <div class="small">{{ $u->role_label }}{{ $u->branch ? ' · '.$u->branch->name : '' }}</div>
                </div>
                <div class="avatar">{{ strtoupper(mb_substr($u->name, 0, 2)) }}</div>
            </div>
        </header>

        <main class="content">
            @if(session('ok'))
                <div class="alert ok">{{ session('ok') }}</div>
            @endif
            @if($errors->any())
                <div class="alert err">
                    <strong>Please fix the following:</strong>
                    <ul>
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>
</body>
</html>
