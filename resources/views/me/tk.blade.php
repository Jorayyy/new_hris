@extends('layouts.app')

@section('title', 'Timekeeping')
@section('content')
    @php
        $punches = [
            'time_in' => 'Time in',
            'break1_out' => 'Break 1 out',
            'break1_in' => 'Break 1 in',
            'lunch_out' => 'Lunch out',
            'lunch_in' => 'Lunch in',
            'break2_out' => 'Break 2 out',
            'break2_in' => 'Break 2 in',
            'time_out' => 'Time out',
        ];
        $fmt = fn ($field) => old($field, optional($existing?->{$field})->format('H:i'));
        $show = fn ($value) => $value?->format('h:i A') ?? '—';
    @endphp

    <div class="dash-head">
        <div>
            <h2 class="hi">Timekeeping</h2>
            <div class="sub">Spotted a wrong or missing punch? Request a fix — HR reviews every change.</div>
        </div>
    </div>

    <div class="grid cols-2">
        <div class="card">
            <div class="card-head"><h2>Request a punch correction</h2></div>
            <div class="card-body">
                <form method="POST" action="{{ route('me.tk.store') }}">
                    @csrf
                    <div class="field"><label>Work date</label>
                        <input type="date" name="work_date" value="{{ old('work_date', optional($existing?->work_date)->format('Y-m-d') ?? optional($date)->format('Y-m-d')) }}" max="{{ now()->toDateString() }}" required>
                    </div>
                    <div class="form-grid">
                        @foreach($punches as $field => $label)
                            <div class="field"><label>{{ $label }}</label><input type="time" name="{{ $field }}" value="{{ $fmt($field) }}"></div>
                        @endforeach
                    </div>
                    <div class="field"><label>Reason</label>
                        <input type="text" name="reason" value="{{ old('reason') }}" placeholder="e.g. Forgot to punch out after shift" required maxlength="500">
                    </div>
                    <p class="muted small">Leave a punch blank if it is already correct. Late, undertime, overtime and night differential are recomputed on approval.</p>
                    <div class="btn-row">
                        <button class="btn primary">Submit request</button>
                        <a class="btn" href="{{ route('me.dtr') }}">View DTR</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-head"><h2>My requests</h2></div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Date</th><th>In</th><th>Out</th><th>Reason</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse($corrections as $c)
                        <tr>
                            <td>{{ $c->work_date->format('M d, Y') }}</td>
                            <td>{{ $show($c->time_in) }}</td>
                            <td>{{ $show($c->time_out) }}</td>
                            <td class="small">{{ $c->reason }}</td>
                            <td>
                                <span class="badge {{ $c->status === 'approved' ? 'green' : ($c->status === 'rejected' ? 'red' : 'amber') }}">{{ ucfirst($c->status) }}</span>
                                @if($c->decision_remarks)<div class="small muted">{{ $c->decision_remarks }}</div>@endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="muted">No correction requests yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
