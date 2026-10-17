@extends('layouts.app')

@section('title', 'Time-clock PINs')
@section('content')
    @if(session('generated_pin'))
        <div class="card" style="max-width:1000px;border-color:var(--primary)">
            <div class="card-body">
                <p class="mb-0"><strong>New PIN for {{ session('generated_pin.name') }}:</strong>
                    <span style="font-size:22px;font-weight:700;letter-spacing:3px">{{ session('generated_pin.pin') }}</span>
                </p>
                <p class="muted small mb-0">Shown once only — write it down now.</p>
            </div>
        </div>
    @endif

    <div class="card" style="max-width:1000px">
        <div class="card-head"><h2>Time-clock PINs</h2></div>
        <div class="card-body">
            <p class="muted small">Employees punch in at the kiosk with their employee number and this 4-digit PIN. PINs are stored hashed and cannot be viewed — they can only be replaced. Saving a blank PIN clears it. Use Generate when an employee forgets their PIN.</p>
            <form method="GET" class="filters">
                <div class="field" style="min-width:220px"><label>Search</label><input type="text" name="q" value="{{ request('q') }}" placeholder="Name or employee no."></div>
                <div class="field" style="min-width:180px"><label>Branch</label>
                    <select name="branch"><option value="">All branches</option>
                        @foreach($branches as $branch)<option value="{{ $branch->id }}" @selected(request('branch') == $branch->id)>{{ $branch->name }}</option>@endforeach
                    </select>
                </div>
                <div class="field"><label style="display:flex;align-items:center;gap:8px;font-weight:500;margin-top:22px"><input type="checkbox" name="missing" value="1" style="width:auto" @checked(request('missing'))> Without a PIN</label></div>
                <button class="btn primary">Filter</button>
                <a class="btn" href="{{ route('pins.index') }}">Reset</a>
            </form>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Employee</th><th>Branch</th><th>PIN</th><th>Set PIN</th><th></th></tr></thead>
                <tbody>
                @forelse($employees as $employee)
                    <tr>
                        <td><div style="font-weight:600">{{ $employee->full_name }}</div><div class="small muted">{{ $employee->employee_no }}</div></td>
                        <td class="muted">{{ $employee->branch?->name ?? '—' }}</td>
                        <td>
                            @if($employee->pin)
                                <span class="badge green">Set</span>
                            @else
                                <span class="badge gray">Not set</span>
                            @endif
                            @if($employee->isPinLocked())
                                <span class="badge" style="background:var(--err-bg);color:var(--err-fg)">Locked until {{ $employee->pin_locked_until->format('g:i A') }}</span>
                            @endif
                        </td>
                        <td>
                            <form method="POST" action="{{ route('pins.update', $employee) }}" class="btn-row">@csrf @method('PUT')
                                <input type="text" name="pin" inputmode="numeric" maxlength="4" pattern="\d{4}" value="{{ old('pin') }}" placeholder="••••" style="width:84px">
                                <button class="btn sm primary">Save</button>
                            </form>
                        </td>
                        <td class="btn-row">
                            <form method="POST" action="{{ route('pins.generate', $employee) }}" onsubmit="return confirm('Generate a new random PIN for {{ $employee->full_name }}?')">@csrf<button class="btn sm">Generate</button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">No employees match.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div style="margin-top:12px">{{ $employees->links() }}</div>
@endsection
