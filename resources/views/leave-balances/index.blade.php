@extends('layouts.app')

@section('title', 'Leave Credits')
@section('content')
    <div class="grid cols-2">
        <div class="card">
            <div class="card-head"><h2>Set employee credits</h2></div>
            <div class="card-body">
                <form method="POST" action="{{ route('leave-balances.store') }}">
                    @csrf
                    <input type="hidden" name="year" value="{{ $year }}">
                    <div class="field">
                        <label>Employee</label>
                        <select name="employee_id" required>
                            <option value="">Select employee</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}" @selected(old('employee_id') == $employee->id)>{{ $employee->full_name }} ({{ $employee->employee_no }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label>Leave type</label>
                        <select name="leave_type_id" required>
                            <option value="">Select type</option>
                            @foreach($leaveTypes as $type)
                                <option value="{{ $type->id }}" @selected(old('leave_type_id') == $type->id)>{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field"><label>Credits (days)</label><input type="number" step="0.01" min="0" name="credits" value="{{ old('credits', 0) }}" required></div>
                    <button class="btn primary">Save credits</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-head"><h2>Run accrual</h2></div>
            <div class="card-body">
                <p class="muted">Creates balances for all active employees using each leave type's annual entitlement (skips existing rows).</p>
                <form method="POST" action="{{ route('leave-balances.accrue') }}">
                    @csrf
                    <div class="field"><label>Year</label><input type="number" name="year" value="{{ $year }}" min="2000" max="2100"></div>
                    <button class="btn primary">Accrue {{ $year }}</button>
                </form>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-head">
            <h2>Balances {{ $year }}</h2>
            <form class="filters" method="GET">
                <input type="hidden" name="year" value="{{ $year }}">
                <div class="field">
                    <select name="employee">
                        <option value="">All employees</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" @selected((int) request('employee') === $employee->id)>{{ $employee->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <select name="type">
                        <option value="">All types</option>
                        @foreach($leaveTypes as $type)
                            <option value="{{ $type->id }}" @selected((int) request('type') === $type->id)>{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="btn">Filter</button>
                <a class="btn" href="{{ route('leave-balances.index', ['year' => $year]) }}">Reset</a>
            </form>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Employee</th><th>Leave type</th><th class="num">Credits</th><th class="num">Used</th><th class="num">Remaining</th><th>Adjust</th></tr></thead>
                <tbody>
                @forelse($balances as $balance)
                    <tr>
                        <td style="font-weight:600">{{ $balance->employee?->full_name }}</td>
                        <td>{{ $balance->leaveType?->name }}</td>
                        <td class="num">{{ number_format((float) $balance->credits, 2) }}</td>
                        <td class="num">{{ number_format((float) $balance->used, 2) }}</td>
                        <td class="num" style="font-weight:600">{{ number_format($balance->remaining, 2) }}</td>
                        <td>
                            <form method="POST" action="{{ route('leave-balances.update', $balance) }}" class="filters" style="gap:6px">
                                @csrf @method('PUT')
                                <input type="number" step="0.01" min="0" name="credits" value="{{ $balance->credits }}" style="width:90px">
                                <button class="btn sm">Save</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted">No balances for {{ $year }}. Run accrual to create them.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="pagination">{{ $balances->links() }}</div>
@endsection
