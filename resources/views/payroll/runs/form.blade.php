@extends('layouts.app')

@section('title', 'New payroll run')
@section('back', route('payroll.runs.index'))
@section('content')
    <div class="card" style="max-width:640px">
        <div class="card-head"><h2>New payroll run</h2></div>
        <div class="card-body">
            <form method="POST" action="{{ route('payroll.runs.store') }}">
                @csrf
                <div class="field"><label>Pay period</label>
                    <select name="payroll_period_id" required><option value="">Select period</option>
                        @foreach($periods as $period)
                            <option value="{{ $period->id }}" @selected(old('payroll_period_id') == $period->id)>{{ $period->name }} ({{ $period->date_from->format('M d') }} – {{ $period->date_to->format('M d, Y') }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="field"><label>Branch</label>
                    <select name="branch_id"><option value="">All branches</option>
                        @foreach($branches as $branch)<option value="{{ $branch->id }}" @selected(old('branch_id') == $branch->id)>{{ $branch->name }}</option>@endforeach
                    </select>
                </div>
                <p class="muted small">A run is created as a draft. Open it and click Process to generate payslips for all active employees.</p>
                <div class="btn-row">
                    <button class="btn primary">Create run</button>
                    <a class="btn" href="{{ route('payroll.runs.index') }}">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
