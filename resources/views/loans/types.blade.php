@extends('layouts.app')

@section('title', 'Loan Types')
@section('content')
    <div class="card">
        <div class="card-head">
            <h2>Loan Types</h2>
        </div>
        <form method="POST" action="{{ route('loan-types.store') }}" class="form-grid card-section">
            @csrf
            <div class="field"><label>Name</label><input name="name" value="{{ old('name') }}" placeholder="e.g. SSS Salary Loan" required></div>
            <div class="field"><label>Interest rate (% per annum)</label><input type="number" step="0.0001" min="0" max="100" name="interest_rate" value="{{ old('interest_rate', 0) }}" required></div>
            <div class="field"><label class="check"><input type="checkbox" name="is_government" value="1"> Government loan</label></div>
            <div class="field"><label class="check"><input type="checkbox" name="is_active" value="1" checked> Active</label></div>
            <div class="field full"><button class="btn primary">Add loan type</button></div>
        </form>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Name</th><th class="num">Interest %</th><th>Government</th><th>Active</th><th class="num">Loans</th><th></th></tr></thead>
                <tbody>
                @forelse($loanTypes as $type)
                    <tr>
                        <td colspan="6" style="padding:0">
                            <form method="POST" action="{{ route('loan-types.update', $type) }}" class="row-form">
                                @csrf @method('PUT')
                                <input name="name" value="{{ $type->name }}" required style="flex:1">
                                <input type="number" step="0.0001" min="0" max="100" name="interest_rate" value="{{ $type->interest_rate }}" style="width:110px">
                                <label class="check"><input type="checkbox" name="is_government" value="1" @checked($type->is_government)> Gov't</label>
                                <label class="check"><input type="checkbox" name="is_active" value="1" @checked($type->is_active)> Active</label>
                                <span class="badge gray">{{ $type->loans_count }} loans</span>
                                <button class="btn sm primary">Save</button>
                            </form>
                            <form method="POST" action="{{ route('loan-types.destroy', $type) }}" onsubmit="return confirm('Delete this loan type?')" style="padding:0 18px 13px">
                                @csrf @method('DELETE')
                                <button class="btn sm danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted">No loan types yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $loanTypes->links() }}
    </div>
@endsection
