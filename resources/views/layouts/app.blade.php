<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('app.css') }}">
    <script>try{var t=localStorage.getItem('hris.theme');if(t){document.documentElement.dataset.theme=t;}}catch(e){}</script>
</head>
<body>
@php
    $u = auth()->user();
    $isEmployee = $u->hasRole(\App\Models\User::ROLE_EMPLOYEE);
    $isHr = $u->hasRole(\App\Models\User::HR_ROLES);
    $isPayroll = $u->hasRole(\App\Models\User::PAYROLL_ROLES);
    $isManager = $u->hasRole(\App\Models\User::ROLE_MANAGER);
    $isAdmin = $u->hasRole([\App\Models\User::ROLE_SUPER_ADMIN, \App\Models\User::ROLE_ADMIN]);
    $unread = $u->unreadNotifications()->count();
@endphp
<div class="layout">
    <input type="checkbox" id="nav-toggle" class="nav-toggle" hidden>
    <aside class="sidebar">
        <div class="brand"><span class="dot"></span> {{ config('app.name') }}</div>

        <nav class="nav-scroll">
        <div class="nav-group">
            <div class="label">Overview</div>
            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}"><x-icon name="layout-dashboard" /> Dashboard</a>
        </div>

        @if($isEmployee)
            <div class="nav-group">
                <div class="label">My Space</div>
                <a href="{{ route('me.dtr') }}" class="{{ request()->routeIs('me.dtr') ? 'active' : '' }}"><x-icon name="clock" /> My DTR</a>
                <a href="{{ route('me.roster') }}" class="{{ request()->routeIs('me.roster') ? 'active' : '' }}"><x-icon name="calendar-days" /> My Roster</a>
                <a href="{{ route('me.tk') }}" class="{{ request()->routeIs('me.tk*') ? 'active' : '' }}"><x-icon name="alarm-clock" /> Timekeeping</a>
                <a href="{{ route('me.payslips') }}" class="{{ request()->routeIs('me.payslips*') ? 'active' : '' }}"><x-icon name="receipt" /> My Payslips</a>
                <a href="{{ route('me.leave') }}" class="{{ request()->routeIs('me.leave') ? 'active' : '' }}"><x-icon name="plane" /> My Leave</a>
                <a href="{{ route('me.swaps') }}" class="{{ request()->routeIs('me.swaps*') ? 'active' : '' }}"><x-icon name="arrow-left-right" /> Shift Swaps</a>
                <a href="{{ route('me.ot') }}" class="{{ request()->routeIs('me.ot*') ? 'active' : '' }}"><x-icon name="trending-up" /> Overtime</a>
                <a href="{{ route('me.schedule') }}" class="{{ request()->routeIs('me.schedule*') ? 'active' : '' }}"><x-icon name="refresh-cw" /> Schedule Change</a>
                <a href="{{ route('me.cases') }}" class="{{ request()->routeIs('me.cases*') ? 'active' : '' }}"><x-icon name="flag" /> My Cases</a>
                <a href="{{ route('me.scorecards') }}" class="{{ request()->routeIs('me.scorecards') ? 'active' : '' }}"><x-icon name="target" /> My Scorecards</a>
                <a href="{{ route('me.pay') }}" class="{{ request()->routeIs('me.pay') ? 'active' : '' }}"><x-icon name="wallet" /> My Pay</a>
                <a href="{{ route('me.profile') }}" class="{{ request()->routeIs('me.profile') ? 'active' : '' }}"><x-icon name="user" /> My Profile</a>
            </div>
        @endif

        @if($u->hasRole(\App\Models\User::HR_ROLES))
            <div class="nav-group">
                <div class="label">People</div>
                <a href="{{ route('employees.index') }}" class="{{ request()->routeIs('employees.*') ? 'active' : '' }}"><x-icon name="users" /> Employees</a>
                <a href="{{ route('documents.index') }}" class="{{ request()->routeIs('documents.*', 'document-types.*') ? 'active' : '' }}"><x-icon name="file-text" /> 201 Documents</a>
                <a href="{{ route('pins.index') }}" class="{{ request()->routeIs('pins.*') ? 'active' : '' }}"><x-icon name="key-round" /> Time-clock PINs</a>
                <a href="{{ route('announcements.index') }}" class="{{ request()->routeIs('announcements.*') ? 'active' : '' }}"><x-icon name="megaphone" /> Announcements</a>
            </div>
            <div class="nav-group">
                <div class="label">Organization</div>
                <a href="{{ route('branches.index') }}" class="{{ request()->routeIs('branches.*') ? 'active' : '' }}"><x-icon name="building-2" /> Branches</a>
                <a href="{{ route('departments.index') }}" class="{{ request()->routeIs('departments.*') ? 'active' : '' }}"><x-icon name="network" /> Departments</a>
                <a href="{{ route('positions.index') }}" class="{{ request()->routeIs('positions.*') ? 'active' : '' }}"><x-icon name="briefcase" /> Positions</a>
            </div>
        @endif

        @if($isHr || $isManager)
            <div class="nav-group">
                <div class="label">Time &amp; Attendance</div>
                <a href="{{ route('attendance.index') }}" class="{{ request()->routeIs('attendance.index', 'attendance.edit') ? 'active' : '' }}"><x-icon name="clock" /> Attendance / DTR</a>
                @if($isHr)
                    <a href="{{ route('attendance.corrections.index') }}" class="{{ request()->routeIs('attendance.corrections.*') ? 'active' : '' }}"><x-icon name="clipboard-check" /> Corrections</a>
                    <a href="{{ route('attendance.import.form') }}" class="{{ request()->routeIs('attendance.import*') ? 'active' : '' }}"><x-icon name="upload" /> Import Biometrics</a>
                    <a href="{{ route('shifts.index') }}" class="{{ request()->routeIs('shifts.*') ? 'active' : '' }}"><x-icon name="calendar-clock" /> Shifts</a>
                    <a href="{{ route('roster.index') }}" class="{{ request()->routeIs('roster.*') ? 'active' : '' }}"><x-icon name="calendar-range" /> Roster</a>
                    <a href="{{ route('holidays.index') }}" class="{{ request()->routeIs('holidays.*') ? 'active' : '' }}"><x-icon name="party-popper" /> Holidays</a>
                @endif
                <a href="{{ route('swaps.index') }}" class="{{ request()->routeIs('swaps.*') ? 'active' : '' }}"><x-icon name="arrow-left-right" /> Shift Swaps</a>
                <a href="{{ route('ot.index') }}" class="{{ request()->routeIs('ot.*') ? 'active' : '' }}"><x-icon name="trending-up" /> Overtime</a>
                <a href="{{ route('schedule-changes.index') }}" class="{{ request()->routeIs('schedule-changes.*') ? 'active' : '' }}"><x-icon name="refresh-cw" /> Schedule Changes</a>
                <a href="{{ route('attendance.exceptions.index') }}" class="{{ request()->routeIs('attendance.exceptions.*') ? 'active' : '' }}"><x-icon name="triangle-alert" /> Exceptions</a>
            </div>
        @endif

        @if($isHr || $isManager)
            <div class="nav-group">
                <div class="label">Recruitment</div>
                <a href="{{ route('vacancies.index') }}" class="{{ request()->routeIs('vacancies.index', 'vacancies.show', 'vacancies.edit', 'applicants.*') ? 'active' : '' }}"><x-icon name="clipboard-list" /> Vacancies</a>
                @if($isHr)
                    <a href="{{ route('vacancies.create') }}" class="{{ request()->routeIs('vacancies.create') ? 'active' : '' }}"><x-icon name="plus" /> New Vacancy</a>
                @endif
            </div>
            <div class="nav-group">
                <div class="label">Employee Relations</div>
                <a href="{{ route('cases.index') }}" class="{{ request()->routeIs('cases.*') ? 'active' : '' }}"><x-icon name="gavel" /> Cases</a>
            </div>
            <div class="nav-group">
                <div class="label">Performance</div>
                <a href="{{ route('qa.index') }}" class="{{ request()->routeIs('qa.index', 'qa.show', 'qa.create', 'qa.edit') ? 'active' : '' }}"><x-icon name="target" /> QA Scorecards</a>
                @if($isHr)
                    <a href="{{ route('qa.criteria') }}" class="{{ request()->routeIs('qa.criteria') ? 'active' : '' }}"><x-icon name="list-checks" /> QA Criteria</a>
                @endif
            </div>
        @endif

        @if($isHr || $isManager || $isPayroll)
            <div class="nav-group">
                <div class="label">Leave</div>
                @if($isHr || $isManager)
                    <a href="{{ route('leave-requests.index') }}" class="{{ request()->routeIs('leave-requests.*') ? 'active' : '' }}"><x-icon name="calendar-minus" /> Leave Requests</a>
                @endif
                @if($isHr)
                    <a href="{{ route('leave-balances.index') }}" class="{{ request()->routeIs('leave-balances.*') ? 'active' : '' }}"><x-icon name="coins" /> Leave Credits</a>
                @endif
                @if($isHr || $isPayroll)
                    <a href="{{ route('leave-types.index') }}" class="{{ request()->routeIs('leave-types.*') ? 'active' : '' }}"><x-icon name="tags" /> Leave Types</a>
                @endif
            </div>
        @endif

        @if($isPayroll)
            <div class="nav-group">
                <div class="label">Reports</div>
                <a href="{{ route('government.index') }}" class="{{ request()->routeIs('government.index') ? 'active' : '' }}"><x-icon name="table" /> Gov't Tables</a>
                <a href="{{ route('government.remittances') }}" class="{{ request()->routeIs('government.remittances') ? 'active' : '' }}"><x-icon name="send" /> Remittances</a>
                <div class="label" style="margin-top:10px">Statutory</div>
                <a href="{{ route('reports.sss-r3') }}" class="{{ request()->routeIs('reports.sss-r3') ? 'active' : '' }}"><x-icon name="file-text" /> SSS R-3</a>
                <a href="{{ route('reports.philhealth-rf1') }}" class="{{ request()->routeIs('reports.philhealth-rf1') ? 'active' : '' }}"><x-icon name="file-text" /> PhilHealth RF-1</a>
                <a href="{{ route('reports.pagibig-mcrf') }}" class="{{ request()->routeIs('reports.pagibig-mcrf') ? 'active' : '' }}"><x-icon name="file-text" /> Pag-IBIG MCRF</a>
                <a href="{{ route('reports.bir-1601c') }}" class="{{ request()->routeIs('reports.bir-1601c') ? 'active' : '' }}"><x-icon name="file-text" /> BIR 1601-C</a>
                <a href="{{ route('reports.bir-2316') }}" class="{{ request()->routeIs('reports.bir-2316') ? 'active' : '' }}"><x-icon name="file-text" /> BIR 2316</a>
            </div>
        @endif

        @if($u->hasRole(\App\Models\User::PAYROLL_ROLES))
            <div class="nav-group">
                <div class="label">Payroll</div>
                <a href="{{ route('payroll.periods.index') }}" class="{{ request()->routeIs('payroll.periods.*') ? 'active' : '' }}"><x-icon name="calendar" /> Pay Periods</a>
                <a href="{{ route('payroll.runs.index') }}" class="{{ request()->routeIs('payroll.runs.*') ? 'active' : '' }}"><x-icon name="circle-play" /> Payroll Runs</a>
                <a href="{{ route('payroll.thirteenth') }}" class="{{ request()->routeIs('payroll.thirteenth') ? 'active' : '' }}"><x-icon name="gift" /> 13th Month</a>
                <a href="{{ route('payroll.final-pay') }}" class="{{ request()->routeIs('payroll.final-pay') ? 'active' : '' }}"><x-icon name="banknote" /> Final Pay</a>
            </div>
        @endif

        @if($isHr || $isPayroll)
            <div class="nav-group">
                <div class="label">Loans</div>
                <a href="{{ route('loans.index') }}" class="{{ request()->routeIs('loans.*') ? 'active' : '' }}"><x-icon name="piggy-bank" /> Employee Loans</a>
                <a href="{{ route('loan-types.index') }}" class="{{ request()->routeIs('loan-types.*') ? 'active' : '' }}"><x-icon name="list" /> Loan Types</a>
            </div>
        @endif

        @if($u->hasRole([\App\Models\User::ROLE_SUPER_ADMIN, \App\Models\User::ROLE_ADMIN]))
            <div class="nav-group">
                <div class="label">Admin</div>
                <a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.*') ? 'active' : '' }}"><x-icon name="settings" /> User Accounts</a>
                <a href="{{ route('audit.index') }}" class="{{ request()->routeIs('audit.*') ? 'active' : '' }}"><x-icon name="scroll-text" /> Audit Log</a>
                <a href="{{ route('operations.index') }}" class="{{ request()->routeIs('operations.*') ? 'active' : '' }}"><x-icon name="activity" /> Operations</a>
                <a href="{{ route('time-clock.ips.index') }}" class="{{ request()->routeIs('time-clock.ips.*') ? 'active' : '' }}"><x-icon name="monitor-smartphone" /> Kiosk Access</a>
                <a href="{{ route('biometric-devices.index') }}" class="{{ request()->routeIs('biometric-devices.*') ? 'active' : '' }}"><x-icon name="fingerprint" /> Biometric Devices</a>
            </div>
        @endif

        </nav>

        <div class="sidebar-foot">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn signout">Sign out</button>
            </form>
        </div>
    </aside>

    <label for="nav-toggle" class="nav-overlay" aria-hidden="true"></label>

    <div class="main">
        <header class="topbar">
            <div class="topbar-left">
                <label for="nav-toggle" class="nav-burger" aria-label="Toggle navigation">☰</label>
                @unless(request()->routeIs('dashboard'))
                    @hasSection('back')
                        <a href="@yield('back')" class="btn sm">← Back</a>
                    @else
                        <button type="button" class="btn sm" onclick="if (history.length > 1) { history.back(); } else { window.location='{{ route('dashboard') }}'; }">← Back</button>
                    @endif
                @endunless
                <h1>@yield('title', 'Dashboard')</h1>
            </div>
            <div class="who">
                <details class="bell">
                    <summary aria-label="Notifications" title="Notifications">🔔@if($unread)<span class="bell-count">{{ $unread }}</span>@endif</summary>
                    <div class="bell-menu">
                        @forelse($u->notifications()->latest()->take(8)->get() as $notification)
                            <a href="{{ route('notifications.read', $notification->id) }}" class="bell-item @unless($notification->read_at) unread @endunless">
                                <span class="ico">{{ $notification->data['icon'] ?? '•' }}</span>
                                <span style="min-width:0">
                                    <span class="bell-title">{{ $notification->data['title'] ?? 'Notification' }}</span>
                                    <div class="small muted">{{ $notification->data['body'] ?? '' }}</div>
                                    <div class="small muted">{{ $notification->created_at->diffForHumans() }}</div>
                                </span>
                            </a>
                        @empty
                            <div class="bell-empty muted small">No notifications yet.</div>
                        @endforelse
                        <a href="{{ route('notifications.index') }}" class="bell-all">View all</a>
                    </div>
                </details>
                <button type="button" class="btn sm" id="theme-toggle" aria-label="Toggle dark mode">Dark</button>
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
            @if(session('error'))
                <div class="alert err">{{ session('error') }}</div>
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
<script>
    (function () {
        var nav = document.querySelector('.nav-scroll');
        if (!nav) return;
        var key = 'hris.sidebar.scroll';
        var saved = sessionStorage.getItem(key);
        if (saved) nav.scrollTop = parseInt(saved, 10) || 0;
        var timer;
        nav.addEventListener('scroll', function () {
            clearTimeout(timer);
            timer = setTimeout(function () { sessionStorage.setItem(key, nav.scrollTop); }, 120);
        });
        window.addEventListener('pagehide', function () { sessionStorage.setItem(key, nav.scrollTop); });
    })();

    (function () {
        var root = document.documentElement;
        var btn = document.getElementById('theme-toggle');
        if (!btn) return;
        function sync() { btn.textContent = root.dataset.theme === 'dark' ? 'Light' : 'Dark'; }
        sync();
        btn.addEventListener('click', function () {
            var dark = root.dataset.theme === 'dark';
            root.dataset.theme = dark ? 'light' : 'dark';
            try { localStorage.setItem('hris.theme', root.dataset.theme); } catch (e) {}
            sync();
        });
    })();
</script>
</body>
</html>
