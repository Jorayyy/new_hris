@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    @php
        $hour = (int) now()->hour;
        $greet = $hour < 12 ? 'morning' : ($hour < 18 ? 'afternoon' : 'evening');
    @endphp

    <div class="dash-head">
        <div>
            <h1 class="hi">Good {{ $greet }}, {{ explode(' ', trim($user->name))[0] }}</h1>
            <div class="sub">{{ $user->role_label }} · {{ now()->format('l, F j, Y') }}</div>
        </div>
        @if($isPayroll)
            <a class="btn primary" href="{{ route('payroll.runs.index') }}">Open payroll</a>
        @endif
    </div>

    @if($isHr || $isManager)
        <div class="grid cols-4" style="margin-bottom:18px">
            <div class="stat"><div class="k">Active employees</div><div class="v">{{ number_format($board['headcount']) }}</div><div class="s">Across all branches</div></div>
            <div class="stat"><div class="k">Present today</div><div class="v">{{ number_format($board['present']) }}</div><div class="s">{{ now()->format('M j') }}</div></div>
            <div class="stat"><div class="k">Late today</div><div class="v">{{ number_format($board['late']) }}</div><div class="s">Past shift start</div></div>
            <div class="stat"><div class="k">On leave</div><div class="v">{{ number_format($board['on_leave']) }}</div><div class="s">Approved, today</div></div>
        </div>

        <div class="grid cols-2">
            <div class="card">
                <div class="card-head"><h2>Currently clocked in</h2><span class="muted small">Live</span></div>
                <div class="card-body">
                    <div class="widget-list">
                        @forelse($clockedIn as $attendance)
                            <div class="widget-row">
                                <div class="who">
                                    <span class="pill-avatar">{{ $attendance->employee?->initials }}</span>
                                    <div>
                                        <div style="font-weight:600">{{ $attendance->employee?->full_name }}</div>
                                        <div class="small muted">{{ $attendance->shift?->name ?? 'No shift assigned' }}</div>
                                    </div>
                                </div>
                                <div class="small muted">in {{ optional($attendance->time_in)->format('g:i A') }}</div>
                            </div>
                        @empty
                            <div class="empty">Nobody is clocked in right now.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-head"><h2>Pending approvals</h2><a class="btn sm" href="{{ route('leave-requests.index') }}">Review</a></div>
                <div class="card-body">
                    @if($pendingLeaveCount > $pendingLeave->count())
                        <div class="small muted" style="margin-bottom:8px">{{ number_format($pendingLeaveCount) }} total pending</div>
                    @endif
                    <div class="widget-list">
                        @forelse($pendingLeave as $leave)
                            <div class="widget-row">
                                <div class="who">
                                    <span class="pill-avatar">{{ $leave->employee?->initials }}</span>
                                    <div>
                                        <div style="font-weight:600">{{ $leave->employee?->full_name }}</div>
                                        <div class="small muted">{{ $leave->leaveType?->name ?? 'Leave' }} · {{ optional($leave->date_from)->format('M j') }} – {{ optional($leave->date_to)->format('M j') }}</div>
                                    </div>
                                </div>
                                <span class="badge amber">{{ ucfirst($leave->status) }}</span>
                            </div>
                        @empty
                            <div class="empty">No pending leave requests.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($isPayroll)
        <div class="grid cols-2">
            <div class="card">
                <div class="card-head"><h2>Net pay trend</h2><span class="muted small">Last {{ $trend->count() }} runs</span></div>
                <div class="card-body">
                    @forelse($trend as $run)
                        <div class="bar-row">
                            <div class="bar-top"><span>{{ $run->period?->name }}</span><span class="muted">{{ peso($run->net_total) }}</span></div>
                            <div class="bar-track"><span style="width:{{ max(2, round($run->net_total / $trendMax * 100)) }}%"></span></div>
                        </div>
                    @empty
                        <div class="empty">No processed payroll runs yet.</div>
                    @endforelse
                </div>
            </div>

            <div class="card">
                <div class="card-head"><h2>Pay cycle</h2>@if($nextPeriod)<span class="badge blue">Open</span>@endif</div>
                <div class="card-body">
                    @if($nextPeriod)
                        <div class="kv" style="grid-template-columns:130px 1fr">
                            <dt>Current period</dt><dd>{{ $nextPeriod->name }}</dd>
                            <dt>Coverage</dt><dd>{{ $nextPeriod->date_from->format('M j') }} – {{ $nextPeriod->date_to->format('M j, Y') }}</dd>
                            <dt>Pay date</dt><dd>{{ $nextPeriod->pay_date->format('M j, Y') }}</dd>
                        </div>
                    @else
                        <div class="empty">No open pay period.</div>
                    @endif
                    @if($lastRun)
                        <div style="margin-top:12px;padding-top:12px;border-top:1px solid var(--border)">
                            <div class="small muted">Last run · {{ $lastRun->period?->name }}</div>
                            <div style="font-size:20px;font-weight:700;letter-spacing:-.02em">{{ peso($lastRun->net_total) }}</div>
                            <div class="small muted">{{ $lastRun->employee_count ?: $lastRun->payslips->count() }} employees · {{ $lastRun->processed_at?->format('M j, Y') }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-head"><h2>Statutory deadlines</h2><span class="muted small">Next filings</span></div>
            <div class="card-body">
                @foreach($statutory as $item)
                    <div class="due">
                        <div><div style="font-weight:600">{{ $item['name'] }}</div><div class="small muted">{{ $item['desc'] }}</div></div>
                        <div style="text-align:right">
                            <div style="font-weight:600">{{ $item['due']->format('M j') }}</div>
                            <div class="small {{ $item['days'] <= 5 ? '' : 'muted' }}">{{ $item['days'] }} day{{ $item['days'] === 1 ? '' : 's' }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if(! $isManager)
        <div class="grid cols-2">
            <div class="card">
                <div class="card-head"><h2>Headcount by branch</h2></div>
                <div class="card-body">
                    @forelse($byBranch as $branch)
                        <div class="bar-row">
                            <div class="bar-top"><span>{{ $branch->name }}</span><span class="muted">{{ $branch->employees_count }}</span></div>
                            <div class="bar-track"><span style="width:{{ max(2, round($branch->employees_count / $branchMax * 100)) }}%"></span></div>
                        </div>
                    @empty
                        <div class="empty">No branches yet.</div>
                    @endforelse
                </div>
            </div>
            <div class="card">
                <div class="card-head"><h2>Headcount by department</h2></div>
                <div class="card-body">
                    @forelse($byDept as $dept)
                        <div class="bar-row">
                            <div class="bar-top"><span>{{ $dept->name }}</span><span class="muted">{{ $dept->employees_count }}</span></div>
                            <div class="bar-track"><span style="width:{{ max(2, round($dept->employees_count / $deptMax * 100)) }}%"></span></div>
                        </div>
                    @empty
                        <div class="empty">No departments yet.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="grid cols-2">
            <div class="card">
                <div class="card-head"><h2>Birthdays this month</h2></div>
                <div class="card-body">
                    <div class="widget-list">
                        @forelse($birthdays as $birthday)
                            <div class="widget-row">
                                <div class="who"><span class="pill-avatar">{{ $birthday->initials }}</span><div style="font-weight:600">{{ $birthday->full_name }}</div></div>
                                <div class="small muted">{{ $birthday->birth_date->format('M j') }}</div>
                            </div>
                        @empty
                            <div class="empty">No birthdays this month.</div>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-head"><h2>Work anniversaries</h2></div>
                <div class="card-body">
                    <div class="widget-list">
                        @forelse($anniversaries as $item)
                            <div class="widget-row">
                                <div class="who"><span class="pill-avatar">{{ $item['employee']->initials }}</span><div style="font-weight:600">{{ $item['employee']->full_name }}</div></div>
                                <span class="badge blue">{{ $item['years'] }} yr{{ $item['years'] === 1 ? '' : 's' }}</span>
                            </div>
                        @empty
                            <div class="empty">No anniversaries this month.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($isHr)
        <div class="grid cols-2">
            <div class="card">
                <div class="card-head"><h2>Attendance watchlist</h2><span class="muted small">This month</span></div>
                <div class="card-body">
                    <div class="widget-list">
                        @forelse($anomalies as $row)
                            <div class="widget-row">
                                <div class="who"><span class="pill-avatar">{{ $row->employee?->initials }}</span><div style="font-weight:600">{{ $row->employee?->full_name }}</div></div>
                                <div class="small muted">{{ (int) $row->late_total }}m late · {{ (int) $row->undertime_total }}m UT</div>
                            </div>
                        @empty
                            <div class="empty">No late or undertime recorded this month.</div>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-head"><h2>Regularization due</h2><span class="muted small">Probationary</span></div>
                <div class="card-body">
                    <div class="widget-list">
                        @forelse($regularization as $employee)
                            <div class="widget-row">
                                <div class="who">
                                    <span class="pill-avatar">{{ $employee->initials }}</span>
                                    <div><div style="font-weight:600">{{ $employee->full_name }}</div><div class="small muted">Hired {{ $employee->date_hired->format('M j, Y') }}</div></div>
                                </div>
                                <span class="badge amber">{{ $employee->date_hired->diffInMonths(now()) }} mo</span>
                            </div>
                        @empty
                            <div class="empty">No probationary employees past 5 months.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if(! $isManager)
        <div class="card">
            <div class="card-head"><h2>Recent activity</h2>@if($isAdmin)<a class="btn sm" href="{{ route('audit.index') }}">View all</a>@endif</div>
            <div class="card-body">
                <div class="widget-list">
                    @forelse($activity as $log)
                        <div class="widget-row">
                            <div class="who">
                                <span class="pill-avatar">·</span>
                                <div>
                                    <div style="font-weight:500">{{ $log->description }}</div>
                                    <div class="small muted">{{ $log->user?->name ?? 'System' }} · {{ $log->created_at?->diffForHumans() }}</div>
                                </div>
                            </div>
                            <span class="badge gray">{{ $log->action }}</span>
                        </div>
                    @empty
                        <div class="empty">No activity recorded yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
    @endif
@endsection
