@extends('layouts.app')

@section('title', $employee->full_name)
@section('back', route('employees.index'))
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
                @php
                    $compliance = $employee->documentCompliance();
                    $stateBadge = ['missing' => 'red', 'expired' => 'red', 'expiring' => 'amber', 'ok' => 'green'];
                    $stateLabel = ['missing' => 'Missing', 'expired' => 'Expired', 'expiring' => 'Expiring', 'ok' => 'OK'];
                @endphp
                @if($compliance->isNotEmpty())
                    <ul style="list-style:none;margin:0 0 14px;padding:0">
                        @foreach($compliance as $row)
                            <li style="display:flex;align-items:center;gap:8px;padding:4px 0">
                                <span class="badge {{ $stateBadge[$row['state']] ?? 'gray' }}">{{ $stateLabel[$row['state']] ?? $row['state'] }}</span>
                                <span>{{ $row['type']->name }}</span>
                                @if($row['document']?->expires_at)
                                    <span class="muted" style="margin-left:auto">{{ $row['document']->expires_at->format('M d, Y') }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
                <form method="POST" action="{{ route('employees.documents.store', $employee) }}" enctype="multipart/form-data" class="filters" style="margin-bottom:14px">
                    @csrf
                    <div class="field"><label>Type</label>
                        <select name="document_type_id" required>
                            <option value="">Select type…</option>
                            @foreach($documentTypes as $type)
                                <option value="{{ $type->id }}" @selected(old('document_type_id') == $type->id)>{{ $type->name }}{{ $type->requires_expiry ? ' *' : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field"><label>Title (optional)</label><input type="text" name="title" value="{{ old('title') }}" placeholder="Defaults to type name"></div>
                    <div class="field"><label>Expires *</label><input type="date" name="expires_at" value="{{ old('expires_at') }}"></div>
                    <div class="field"><label>File</label><input type="file" name="file" required></div>
                    <button class="btn primary">Upload</button>
                </form>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Type</th><th>Title</th><th>Expires</th><th></th></tr></thead>
                        <tbody>
                        @forelse($employee->documents as $document)
                            <tr>
                                <td>{{ $document->documentType?->name ?? $document->type }}</td>
                                <td><a href="{{ \Illuminate\Support\Facades\Storage::url($document->file_path) }}" target="_blank">{{ $document->title }}</a></td>
                                <td>
                                    {{ $document->expires_at?->format('M d, Y') ?? '—' }}
                                    @if($document->expiryState() && $document->expiryState() !== 'ok')
                                        <span class="badge {{ $stateBadge[$document->expiryState()] }}">{{ $stateLabel[$document->expiryState()] }}</span>
                                    @endif
                                </td>
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
        <div class="card-head">
            <h2>Employment Lifecycle</h2>
            @if($employee->is_active)
                <span class="badge green">Active</span>
            @else
                <span class="badge gray">Separated {{ $employee->date_separated?->format('M d, Y') }}</span>
            @endif
        </div>
        <div class="card-body">
            <div class="grid cols-2">
                <div>
                    <h3 style="margin:0 0 8px">Regularization</h3>
                    <dl class="kv">
                        <dt>Status</dt><dd>{{ ucfirst($employee->employment_status) }}</dd>
                        <dt>Probation</dt><dd>{{ $employee->probation_months }} month(s)</dd>
                        <dt>Due on</dt>
                        <dd>
                            {{ $employee->regularizationDueOn()?->format('M d, Y') ?? '—' }}
                            @if($employee->isForRegularization() && $employee->regularizationDaysLeft() !== null)
                                @php $daysLeft = $employee->regularizationDaysLeft(); @endphp
                                <span class="badge {{ $daysLeft < 0 ? 'red' : ($daysLeft <= \App\Models\Employee::REGULARIZATION_WARN_DAYS ? 'amber' : 'gray') }}">
                                    {{ $daysLeft < 0 ? abs($daysLeft).' days overdue' : $daysLeft.' days left' }}
                                </span>
                            @endif
                        </dd>
                        <dt>Regularized</dt><dd>{{ $employee->date_regularized?->format('M d, Y') ?? '—' }}</dd>
                    </dl>
                    @if($employee->isForRegularization())
                        <form method="POST" action="{{ route('employees.regularize', $employee) }}" onsubmit="return confirm('Mark this employee as regular?')">
                            @csrf
                            <button class="btn primary">Mark as regular</button>
                        </form>
                    @endif
                </div>
                <div>
                    <h3 style="margin:0 0 8px">Separation &amp; clearance</h3>
                    @if($employee->is_active)
                        <form method="POST" action="{{ route('employees.separate', $employee) }}" onsubmit="return confirm('Separate this employee? Their login will be disabled and a clearance checklist created.')" class="filters">
                            @csrf
                            <div class="field"><label>Last day</label><input type="date" name="date_separated" value="{{ now()->toDateString() }}"></div>
                            <div class="field"><label>Reason</label><input name="separation_reason" placeholder="e.g. Resigned, End of contract"></div>
                            <button class="btn danger">Separate employee</button>
                        </form>
                    @else
                        <p class="muted small">
                            Separated on {{ $employee->date_separated?->format('M d, Y') ?? '—' }}
                            @if($employee->separation_reason) · {{ $employee->separation_reason }} @endif
                        </p>
                        @php $progress = $employee->clearanceProgress(); @endphp
                        <p class="small">Clearance: <strong>{{ $progress['done'] }} / {{ $progress['total'] }}</strong> done</p>
                        <div class="table-wrap">
                            <table>
                                <thead><tr><th>Item</th><th></th><th>Done by</th></tr></thead>
                                <tbody>
                                @forelse($employee->clearanceItems as $item)
                                    <tr>
                                        <td>{{ $item->label }}</td>
                                        <td>
                                            <form method="POST" action="{{ route('clearance-items.update', $item) }}">
                                                @csrf @method('PUT')
                                                <input type="hidden" name="is_done" value="{{ $item->is_done ? 0 : 1 }}">
                                                <button class="btn sm {{ $item->is_done ? 'ghost' : 'primary' }}">{{ $item->is_done ? 'Undo' : 'Done' }}</button>
                                            </form>
                                        </td>
                                        <td class="muted small">{{ $item->is_done ? ($item->doneBy?->name ?? '—').' · '.$item->done_at?->format('M d, Y') : '—' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="muted">No clearance checklist yet.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
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
