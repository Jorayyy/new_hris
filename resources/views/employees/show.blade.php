@extends('layouts.app')

@section('title', $employee->full_name)
@section('content')
    <div class="card">
        <div class="card-head">
            <div style="display:flex;align-items:center;gap:16px">
                <div class="avatar" style="width:52px;height:52px;font-size:18px">{{ $employee->initials }}</div>
                <div>
                    <h2>{{ $employee->full_name }}</h2>
                    <div class="muted small">{{ $employee->employee_no }} · {{ $employee->position?->title ?? '—' }} · {{ $employee->branch?->name ?? '—' }}</div>
                </div>
            </div>
            <div class="btn-row">
                <span class="badge {{ $employee->is_active ? 'green' : 'gray' }}">{{ ucfirst($employee->employment_status) }}</span>
                <a class="btn sm" href="{{ route('employees.edit', $employee) }}">Edit</a>
            </div>
        </div>
        <div class="card-body">
            <dl class="kv">
                <dt>Department</dt><dd>{{ $employee->department?->name ?? '—' }}</dd>
                <dt>Date hired</dt><dd>{{ $employee->date_hired?->format('M d, Y') ?? '—' }}</dd>
                <dt>Basic salary</dt><dd>{{ peso($employee->basic_salary) }} / month</dd>
                <dt>Daily rate</dt><dd>{{ peso($employee->dailyRate()) }}</dd>
                <dt>Contact</dt><dd>{{ $employee->contact_no ?? '—' }}</dd>
                <dt>Email</dt><dd>{{ $employee->personal_email ?? '—' }}</dd>
                <dt>SSS / PhilHealth / Pag-IBIG</dt><dd>{{ $employee->sss_no ?? '—' }} / {{ $employee->philhealth_no ?? '—' }} / {{ $employee->pagibig_no ?? '—' }}</dd>
                <dt>TIN</dt><dd>{{ $employee->tin ?? '—' }}</dd>
                <dt>Bank</dt><dd>{{ $employee->bank_name ?? '—' }} {{ $employee->bank_account_no }}</dd>
            </dl>
        </div>
    </div>

    <div class="grid cols-2">
        <div class="card">
            <div class="card-head"><h2>201 Documents</h2></div>
            <div class="card-body">
                <form method="POST" action="{{ route('employees.documents.store', $employee) }}" enctype="multipart/form-data" class="filters" style="margin-bottom:14px">
                    @csrf
                    <div class="field"><label>Type</label><input type="text" name="type" placeholder="e.g. NBI Clearance" required></div>
                    <div class="field"><label>Title</label><input type="text" name="title" placeholder="Title" required></div>
                    <div class="field"><label>Expires</label><input type="date" name="expires_at"></div>
                    <div class="field"><label>File</label><input type="file" name="file" required></div>
                    <button class="btn primary">Upload</button>
                </form>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Type</th><th>Title</th><th>Expires</th><th></th></tr></thead>
                        <tbody>
                        @forelse($employee->documents as $document)
                            <tr>
                                <td>{{ $document->type }}</td>
                                <td><a href="{{ \Illuminate\Support\Facades\Storage::url($document->file_path) }}" target="_blank">{{ $document->title }}</a></td>
                                <td>{{ $document->expires_at?->format('M d, Y') ?? '—' }}</td>
                                <td><form method="POST" action="{{ route('documents.destroy', $document) }}">@csrf @method('DELETE')<button class="btn sm danger">Remove</button></form></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="muted">No documents uploaded.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-head"><h2>Leave Balances ({{ now()->year }})</h2></div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Type</th><th class="num">Credits</th><th class="num">Used</th><th class="num">Remaining</th></tr></thead>
                    <tbody>
                    @forelse($leaveBalances as $balance)
                        <tr>
                            <td>{{ $balance->leaveType?->name }}</td>
                            <td class="num">{{ number_format($balance->credits, 2) }}</td>
                            <td class="num">{{ number_format($balance->used, 2) }}</td>
                            <td class="num">{{ number_format($balance->remaining, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="muted">No leave credits on file.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><h2>Recent Payslips</h2></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Period</th><th class="num">Gross</th><th class="num">Deductions</th><th class="num">Net</th><th></th></tr></thead>
                <tbody>
                @forelse($payslips as $payslip)
                    <tr>
                        <td>{{ $payslip->run?->period?->name }}</td>
                        <td class="num">{{ peso($payslip->gross_pay) }}</td>
                        <td class="num">{{ peso($payslip->total_deductions) }}</td>
                        <td class="num" style="font-weight:600">{{ peso($payslip->net_pay) }}</td>
                        <td>@if(auth()->user()->hasRole(\App\Models\User::PAYROLL_ROLES))<a class="btn sm ghost" href="{{ route('payslips.show', $payslip) }}">View</a>@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">No payslips yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
