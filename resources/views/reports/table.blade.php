@extends('layouts.app')

@section('title', $title)
@section('content')
    <div class="card">
        <div class="card-head">
            <div>
                <h2>{{ $title }}</h2>
                <div class="muted small">{{ $subtitle }}</div>
            </div>
            <div class="btn-row">
                <a class="btn" href="{{ request()->fullUrlWithQuery(['csv' => 1]) }}">Download CSV</a>
                <button class="btn" onclick="window.print()">Print</button>
            </div>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr>@foreach($headers as $header)<th>{{ $header }}</th>@endforeach</tr></thead>
                <tbody>
                @forelse($rows as $row)
                    <tr>@foreach($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>
                @empty
                    <tr><td colspan="{{ count($headers) }}" class="muted">No data. Process a payroll run first.</td></tr>
                @endforelse
                </tbody>
                @if($totals)
                    <tfoot><tr>@foreach($totals as $cell)<th>{{ $cell }}</th>@endforeach</tr></tfoot>
                @endif
            </table>
        </div>
    </div>
@endsection
