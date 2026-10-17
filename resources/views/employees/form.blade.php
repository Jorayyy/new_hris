@extends('layouts.app')

@section('title', $employee->exists ? 'Edit employee' : 'New employee')
@section('back', $employee->exists ? route('employees.show', $employee) : route('employees.index'))
@section('content')
    <form method="POST" action="{{ $employee->exists ? route('employees.update', $employee) : route('employees.store') }}">
        @csrf
        @if($employee->exists) @method('PUT') @endif

        <div class="card">
            <div class="card-head"><h2>Personal Information</h2></div>
            <div class="card-body">
                <div class="form-grid">
                    <div class="field"><label>Employee No.</label><input type="text" name="employee_no" value="{{ old('employee_no', $employee->employee_no) }}" required></div>
                    <div class="field"><label>Time-clock PIN (4 digits{{ $employee->exists && $employee->pin ? ', blank = keep current' : '' }})</label><input type="text" name="pin" inputmode="numeric" maxlength="4" pattern="\d{4}" value="{{ old('pin') }}" placeholder="••••"></div>
                    <div class="field"><label>Date hired</label><input type="date" name="date_hired" value="{{ old('date_hired', optional($employee->date_hired)->format('Y-m-d')) }}"></div>
                    <div class="field"><label>First name</label><input type="text" name="first_name" value="{{ old('first_name', $employee->first_name) }}" required></div>
                    <div class="field"><label>Middle name</label><input type="text" name="middle_name" value="{{ old('middle_name', $employee->middle_name) }}"></div>
                    <div class="field"><label>Last name</label><input type="text" name="last_name" value="{{ old('last_name', $employee->last_name) }}" required></div>
                    <div class="field"><label>Suffix</label><input type="text" name="suffix" value="{{ old('suffix', $employee->suffix) }}"></div>
                    <div class="field"><label>Birth date</label><input type="date" name="birth_date" value="{{ old('birth_date', optional($employee->birth_date)->format('Y-m-d')) }}"></div>
                    <div class="field"><label>Gender</label>
                        <select name="gender">
                            <option value="">—</option>
                            @foreach(['Male','Female'] as $g)
                                <option @selected(old('gender', $employee->gender) === $g)>{{ $g }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field"><label>Civil status</label>
                        <select name="civil_status">
                            <option value="">—</option>
                            @foreach(['Single','Married','Widowed','Separated'] as $c)
                                <option @selected(old('civil_status', $employee->civil_status) === $c)>{{ $c }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field"><label>Contact no.</label><input type="text" name="contact_no" value="{{ old('contact_no', $employee->contact_no) }}"></div>
                    <div class="field"><label>Personal email</label><input type="email" name="personal_email" value="{{ old('personal_email', $employee->personal_email) }}"></div>
                    <div class="field full"><label>Address</label><input type="text" name="address" value="{{ old('address', $employee->address) }}"></div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-head"><h2>Employment</h2></div>
            <div class="card-body">
                <div class="form-grid">
                    <div class="field"><label>Branch</label>
                        <select name="branch_id"><option value="">—</option>
                            @foreach($branches as $branch)<option value="{{ $branch->id }}" @selected(old('branch_id', $employee->branch_id) == $branch->id)>{{ $branch->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="field"><label>Department</label>
                        <select name="department_id"><option value="">—</option>
                            @foreach($departments as $dept)<option value="{{ $dept->id }}" @selected(old('department_id', $employee->department_id) == $dept->id)>{{ $dept->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="field"><label>Position</label>
                        <select name="position_id"><option value="">—</option>
                            @foreach($positions as $pos)<option value="{{ $pos->id }}" @selected(old('position_id', $employee->position_id) == $pos->id)>{{ $pos->title }}</option>@endforeach
                        </select>
                    </div>
                    <div class="field"><label>Employment status</label>
                        <select name="employment_status">
                            @foreach(['probationary','regular','contractual','project','part-time'] as $s)
                                <option value="{{ $s }}" @selected(old('employment_status', $employee->employment_status) === $s)>{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field"><label>Date regularized</label><input type="date" name="date_regularized" value="{{ old('date_regularized', optional($employee->date_regularized)->format('Y-m-d')) }}"></div>
                    <div class="field"><label>Tax status</label>
                        <select name="tax_status">
                            @foreach(['S','S1','S2','S3','S4','ME','ME1','ME2','ME3','ME4'] as $t)
                                <option @selected(old('tax_status', $employee->tax_status) === $t)>{{ $t }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field"><label># of dependents</label><input type="number" name="dependents_count" min="0" max="20" value="{{ old('dependents_count', $employee->dependents_count ?? 0) }}"></div>
                    <div class="field full"><label>Rest days</label>
                        <div class="btn-row" style="gap:16px">
                            @foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $i => $day)
                                <label style="display:flex;align-items:center;gap:6px;font-weight:500"><input type="checkbox" name="rest_days[]" value="{{ $i }}" style="width:auto" @checked(in_array($i, array_map('intval', old('rest_days', $employee->rest_days ?? [])), true))> {{ $day }}</label>
                            @endforeach
                        </div>
                    </div>
                    <div class="field"><label style="display:flex;align-items:center;gap:8px;font-weight:500;margin-top:22px"><input type="checkbox" name="is_active" value="1" style="width:auto" @checked(old('is_active', $employee->is_active ?? true))> Active</label></div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-head"><h2>Compensation &amp; Government IDs</h2></div>
            <div class="card-body">
                <div class="form-grid">
                    <div class="field"><label>Basic salary (monthly)</label><input type="number" step="0.01" name="basic_salary" value="{{ old('basic_salary', $employee->basic_salary ?? 0) }}" required></div>
                    <div class="field"><label>Monthly allowance</label><input type="number" step="0.01" name="monthly_allowance" value="{{ old('monthly_allowance', $employee->monthly_allowance ?? 0) }}"></div>
                    <div class="field"><label>Daily rate (blank = basic ÷ 22)</label><input type="number" step="0.01" name="daily_rate" value="{{ old('daily_rate', $employee->daily_rate ?? 0) }}"></div>
                    <div class="field"><label>Bank</label><input type="text" name="bank_name" value="{{ old('bank_name', $employee->bank_name) }}"></div>
                    <div class="field"><label>Bank account no.</label><input type="text" name="bank_account_no" value="{{ old('bank_account_no', $employee->bank_account_no) }}"></div>
                    <div class="field"><label>SSS no.</label><input type="text" name="sss_no" value="{{ old('sss_no', $employee->sss_no) }}"></div>
                    <div class="field"><label>PhilHealth no.</label><input type="text" name="philhealth_no" value="{{ old('philhealth_no', $employee->philhealth_no) }}"></div>
                    <div class="field"><label>Pag-IBIG no.</label><input type="text" name="pagibig_no" value="{{ old('pagibig_no', $employee->pagibig_no) }}"></div>
                    <div class="field"><label>TIN</label><input type="text" name="tin" value="{{ old('tin', $employee->tin) }}"></div>
                </div>
            </div>
        </div>

        <div class="btn-row">
            <button class="btn primary">Save employee</button>
            <a class="btn" href="{{ $employee->exists ? route('employees.show', $employee) : route('employees.index') }}">Cancel</a>
        </div>
    </form>
@endsection
