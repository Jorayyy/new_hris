@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="grid cols-4" style="margin-bottom:20px">
        <div class="stat">
            <div class="k">Active Employees</div>
            <div class="v">{{ number_format($stats['employees']) }}</div>
            <div class="s">Across all branches</div>
        </div>
        <div class="stat">
            <div class="k">Present Today</div>
            <div class="v">{{ number_format($stats['present_today']) }}</div>
            <div class="s">{{ now()->format('M d, Y') }}</div>
        </div>
        <div class="stat">
            <div class="k">On Leave Today</div>
            <div class="v">{{ number_format($stats['on_leave']) }}</div>
            <div class="s">Approved leaves</div>
        </div>
        <div class="stat">
            <div class="k">Pending Leaves</div>
            <div class="v">{{ number_format($stats['pending_leave']) }}</div>
            <div class="s">Awaiting approval</div>
        </div>
    </div>

    <div class="grid cols-2">
        <div class="card">
            <div class="card-head">
                <h2>Recent Payroll Runs</h2>
                @if(auth()->user()->hasRole(\App\Models\User::PAYROLL_ROLES))
                    <a class="btn sm" href="{{ route('payroll.runs.index') }}">View all</a>
                @endif
            </div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Period</th><th>Branch</th><th>Status</th><th class="num">Net</th></tr></thead>
                    <tbody>
                    @forelse($recentPayroll as $run)
                        <tr>
                            <td>{{ $run->period?->name }}</td>
                            <td>{{ $run->branch?->name ?? 'All branches' }}</td>
                            <td><span class="badge {{ $run->status === 'processed' ? 'green' : 'gray' }}">{{ ucfirst($run->status) }}</span></td>
                            <td class="num">{{ peso($run->net_total) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="muted">No payroll runs yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-head"><h2>Open Pay Periods</h2></div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Name</th><th>Coverage</th><th>Pay Date</th></tr></thead>
                    <tbody>
                    @forelse($openPeriods as $period)
                        <tr>
                            <td>{{ $period->name }}</td>
                            <td class="small">{{ $period->date_from->format('M d') }} – {{ $period->date_to->format('M d, Y') }}</td>
                            <td>{{ $period->pay_date->format('M d, Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="muted">No open periods.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    <div class="grid cols-2" style="margin-top:20px">
        <div class="card">
            <div class="card-head"><h2>Net Pay Trend</h2><span class="muted small">Last {{ $trend->count() }} processed runs</span></div>
            <div class="card-body">
                @forelse($trend as $run)
                    <div style="margin-bottom:12px">
                        <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px">
                            <span>{{ $run->period?->name }}</span><span class="muted">{{ peso($run->net_total) }}</span>
                        </div>
                        <div style="background:#edeae3;border-radius:6px;height:10px;overflow:hidden">
                            <div style="height:100%;width:{{ max(2, round($run->net_total / $trendMax * 100)) }}%;background:var(--primary)"></div>
                        </div>
                    </div>
                @empty
                    <p class="muted">No processed payroll runs yet.</p>
                @endforelse
            </div>
        </div>

        <div class="card">
            <div class="card-head"><h2>Attendance This Month</h2><span class="muted small">{{ now()->format('F Y') }}</span></div>
            <div class="card-body">
                @php($labels = ['present' => 'Present', 'absent' => 'Absent', 'leave' => 'Leave', 'holiday' => 'Holiday'])
                @forelse($labels as $key => $label)
                    <div style="margin-bottom:12px">
                        <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px">
                            <span>{{ $label }}</span><span class="muted">{{ number_format($attendance[$key] ?? 0) }}</span>
                        </div>
                        <div style="background:#edeae3;border-radius:6px;height:10px;overflow:hidden">
                            <div style="height:100%;width:{{ max(2, round(($attendance[$key] ?? 0) / $attendanceMax * 100)) }}%;background:var(--primary)"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
