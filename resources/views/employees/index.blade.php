@extends('layouts.app')

@section('title', 'Employees')
@section('content')
    <div class="card">
        <div class="card-head">
            <h2>Employees</h2>
            <div class="btn-row">
                <a class="btn sm" href="{{ route('employees.import.form') }}">Import CSV</a>
                <a class="btn sm" href="{{ route('employees.export', request()->query()) }}">Export CSV</a>
                <a class="btn primary sm" href="{{ route('employees.create') }}">+ New employee</a>
            </div>
        </div>
        <div class="card-body" style="padding-bottom:0">
            <form method="GET" class="filters">
                <div class="field">
                    <label>Search</label>
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Name or employee no.">
                </div>
                <div class="field">
                    <label>Branch</label>
                    <select name="branch">
                        <option value="">All</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" @selected(request('branch') == $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Status</label>
                    <select name="status">
                        <option value="">All</option>
                        @foreach(['regular','probationary','contractual','project','part-time'] as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="btn primary">Filter</button>
                <a class="btn" href="{{ route('employees.index') }}">Reset</a>
            </form>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Employee</th><th>No.</th><th>Branch</th><th>Department</th><th>Position</th><th>Status</th><th class="num">Basic</th><th></th></tr></thead>
                <tbody>
                @forelse($employees as $employee)
                    <tr>
                        <td style="font-weight:600">{{ $employee->full_name }}</td>
                        <td>{{ $employee->employee_no }}</td>
                        <td>{{ $employee->branch?->name ?? '—' }}</td>
                        <td>{{ $employee->department?->name ?? '—' }}</td>
                        <td>{{ $employee->position?->title ?? '—' }}</td>
                        <td><span class="badge {{ $employee->is_active ? 'green' : 'gray' }}">{{ ucfirst($employee->employment_status) }}</span></td>
                        <td class="num">{{ peso($employee->basic_salary) }}</td>
                        <td><a class="btn sm" href="{{ route('employees.show', $employee) }}">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="muted">No employees found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $employees->links() }}
    </div>
@endsection
