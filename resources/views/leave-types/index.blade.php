@extends('layouts.app')

@section('title', 'Leave Types')
@section('content')
    <div class="card">
        <div class="card-head">
            <h2>Leave Types</h2>
            <a class="btn primary sm" href="{{ route('leave-types.create') }}">+ New leave type</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Name</th><th>Code</th><th>Paid</th><th class="num">Pay %</th><th class="num">Max days/yr</th><th>Cash convertible</th><th>Active</th><th></th></tr></thead>
                <tbody>
                @forelse($leaveTypes as $leaveType)
                    <tr>
                        <td style="font-weight:600">{{ $leaveType->name }}</td>
                        <td>{{ $leaveType->code }}</td>
                        <td><span class="badge {{ $leaveType->is_paid ? 'green' : 'gray' }}">{{ $leaveType->is_paid ? 'Paid' : 'Unpaid' }}</span></td>
                        <td class="num">{{ $leaveType->pay_percentage }}%</td>
                        <td class="num">{{ $leaveType->max_days_per_year }}</td>
                        <td><span class="badge {{ $leaveType->is_cash_convertible ? 'cyan' : 'gray' }}">{{ $leaveType->is_cash_convertible ? 'Yes' : 'No' }}</span></td>
                        <td><span class="badge {{ $leaveType->is_active ? 'green' : 'gray' }}">{{ $leaveType->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td class="btn-row">
                            <a class="btn sm" href="{{ route('leave-types.edit', $leaveType) }}">Edit</a>
                            <form method="POST" action="{{ route('leave-types.destroy', $leaveType) }}" onsubmit="return confirm('Delete this leave type?')">
                                @csrf @method('DELETE')
                                <button class="btn sm danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="muted">No leave types yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $leaveTypes->links() }}
    </div>
@endsection
