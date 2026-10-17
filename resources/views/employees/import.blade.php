@extends('layouts.app')

@section('title', 'Import Employees')
@section('back', route('employees.index'))
@section('content')
    @php($result = session('import_result'))
    <div class="card" style="max-width:760px">
        <div class="card-head"><h2>Import employees from CSV</h2></div>
        <div class="card-body">
            @if($result)
                <div class="alert {{ $result['skipped'] ? 'err' : 'ok' }}">
                    {{ $result['preview'] ? 'Preview' : 'Import' }} result:
                    {{ $result['created'] }} new, {{ $result['updated'] }} updated, {{ $result['skipped'] }} skipped.
                    @if($result['errors'])
                        <ul>
                            @foreach($result['errors'] as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endif

            <p class="muted small">The first row is treated as a header. Columns are matched by name, in any order. Recognised columns:</p>
            <table style="margin-bottom:16px">
                <thead><tr><th>Required</th><th>Optional</th></tr></thead>
                <tbody>
                    <tr>
                        <td>employee_no, first_name, last_name</td>
                        <td>middle_name, suffix, branch, department, position, employment_status, date_hired, basic_salary, monthly_allowance, tax_status, contact_no, personal_email, sss_no, philhealth_no, pagibig_no, tin, bank_name, bank_account_no</td>
                    </tr>
                </tbody>
            </table>
            <p class="muted small">Branch, department and position are matched by name against existing records; unmatched values are left blank. Rows are matched by employee number: an existing employee number updates that record, otherwise a new employee is created. Invalid rows are skipped and listed above.</p>
            <form method="POST" action="{{ route('employees.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="field"><label>CSV file</label><input type="file" name="file" accept=".csv,text/csv" required></div>
                <label style="display:flex;align-items:center;gap:8px;margin-bottom:14px"><input type="checkbox" name="preview" value="1"> Preview only (validate without saving)</label>
                <div class="btn-row">
                    <button class="btn primary">Upload</button>
                    <a class="btn" href="{{ route('employees.index') }}">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
