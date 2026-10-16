@extends('layouts.app')

@section('title', '13th Month Pay')
@section('content')
    <div class="card">
        <div class="card-head">
            <h2>13th Month Pay — {{ $year }}</h2>
            <form method="GET" class="filters">
                <div class="field"><label>Year</label><input type="number" name="year" value="{{ $year }}" min="2000" max="2100"></div>
                <button class="btn primary">View</button>
            </form>
        </div>
        <div class="card-body" style="padding-bottom:0">
            <p class="muted small">Computed as total basic salary earned in {{ $year }} ÷ 12. Only processed payroll runs are included.</p>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Employee</th><th>Branch</th><th class="num">13th Month Pay</th></tr></thead>
                <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td style="font-weight:600">{{ $row['employee']->full_name }}</td>
                        <td>{{ $row['employee']->branch?->name ?? '—' }}</td>
                        <td class="num">{{ peso($row['amount']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="muted">No employees.</td></tr>
                @endforelse
                </tbody>
                <tfoot>
                    <tr style="font-weight:700;background:var(--surface-2)"><td colspan="2">Total</td><td class="num">{{ peso($total) }}</td></tr>
                </tfoot>
            </table>
        </div>
    </div>
@endsection
