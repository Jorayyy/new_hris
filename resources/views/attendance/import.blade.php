@extends('layouts.app')

@section('title', 'Import Biometrics')
@section('content')
    <div class="card" style="max-width:720px">
        <div class="card-head"><h2>Import attendance CSV</h2></div>
        <div class="card-body">
            <p class="muted small">Upload a CSV exported from your biometric device. The first row is treated as a header. Columns, in order:</p>
            <table style="margin-bottom:16px">
                <thead><tr><th>employee_no</th><th>date</th><th>time_in</th><th>time_out</th></tr></thead>
                <tbody><tr><td>EMP0001</td><td>2026-01-15</td><td>08:00</td><td>17:00</td></tr></tbody>
            </table>
            <p class="muted small">Rows are matched by employee number and upserted by date, then processed through the DTR engine. Unknown employee numbers are skipped.</p>
            <form method="POST" action="{{ route('attendance.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="field"><label>CSV file</label><input type="file" name="file" accept=".csv,text/csv" required></div>
                <div class="btn-row">
                    <button class="btn primary">Upload &amp; process</button>
                    <a class="btn" href="{{ route('attendance.index') }}">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
