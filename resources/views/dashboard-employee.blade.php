@extends('layouts.app')

@section('title', 'My Dashboard')

@section('content')
    @if(! $employee)
        <div class="alert err">Your account is not yet linked to an employee record. Please contact HR.</div>
    @else
        <div class="grid cols-3" style="margin-bottom:20px">
            <div class="stat">
                <div class="k">Today's Attendance</div>
                @if($attendance && $attendance->time_in)
                    <div class="v" style="font-size:20px">
                        {{ $attendance->time_in->format('h:i A') }}
                        @if($attendance->time_out) – {{ $attendance->time_out->format('h:i A') }} @endif
                    </div>
                    <div class="s">
                        @if(! $attendance->time_out) <span class="badge amber">Timed in</span>
                        @else <span class="badge green">Complete</span> @endif
                        {{ hrs($attendance->hours_worked) }} hrs
                    </div>
                @else
                    <div class="v">—</div>
                    <div class="s">No attendance recorded today</div>
                @endif
            </div>
            <div class="stat">
                <div class="k">Position</div>
                <div class="v" style="font-size:18px">{{ $employee->position?->title ?? '—' }}</div>
                <div class="s">{{ $employee->department?->name ?? '—' }} · {{ $employee->branch?->name ?? '—' }}</div>
            </div>
            <div class="stat">
                <div class="k">Date Hired</div>
                <div class="v" style="font-size:18px">{{ $employee->date_hired?->format('M d, Y') ?? '—' }}</div>
                <div class="s">{{ ucfirst($employee->employment_status) }}</div>
            </div>
        </div>

        <div class="grid cols-2">
            <div class="card">
                <div class="card-head">
                    <h2>Recent Payslips</h2>
                    <a class="btn sm" href="{{ route('me.payslips') }}">View all</a>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Period</th><th class="num">Net pay</th><th></th></tr></thead>
                        <tbody>
                        @forelse($payslips as $payslip)
                            <tr>
                                <td>{{ $payslip->run?->period?->name }}</td>
                                <td class="num">{{ peso($payslip->net_pay) }}</td>
                                <td><a class="btn sm ghost" href="{{ route('me.payslips.show', $payslip) }}">View</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="muted">No payslips yet.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-head"><h2>Leave Balances ({{ now()->year }})</h2></div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Type</th><th class="num">Remaining</th></tr></thead>
                        <tbody>
                        @forelse($leaveBalances as $balance)
                            <tr>
                                <td>{{ $balance->leaveType?->name }}</td>
                                <td class="num">{{ number_format($balance->remaining, 2) }} days</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="muted">No leave credits on file.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
@endsection
