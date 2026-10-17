@extends('layouts.app')

@section('title', 'Document Types')
@section('content')
    <div class="card">
        <div class="card-head">
            <h2>Document Types</h2>
            <span class="muted">Required types appear on every employee's 201-file checklist.</span>
        </div>
        <form method="POST" action="{{ route('document-types.store') }}" class="form-grid card-section">
            @csrf
            <div class="field"><label>Name</label><input name="name" value="{{ old('name') }}" placeholder="e.g. NBI Clearance" required></div>
            <div class="field"><label>Sort order</label><input type="number" min="0" max="999" name="sort" value="{{ old('sort', 0) }}"></div>
            <div class="field"><label style="display:flex;align-items:center;gap:8px;font-weight:500;margin-top:22px"><input type="checkbox" name="is_required" value="1" style="width:auto"> Required</label></div>
            <div class="field"><label style="display:flex;align-items:center;gap:8px;font-weight:500;margin-top:22px"><input type="checkbox" name="requires_expiry" value="1" style="width:auto"> Needs expiry date</label></div>
            <div class="field"><label style="display:flex;align-items:center;gap:8px;font-weight:500;margin-top:22px"><input type="checkbox" name="is_active" value="1" style="width:auto" checked> Active</label></div>
            <div class="field full"><button class="btn primary">Add document type</button></div>
        </form>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Name</th><th class="num">Sort</th><th>Required</th><th>Needs expiry</th><th>Active</th><th class="num">Files</th><th></th></tr></thead>
                <tbody>
                @forelse($types as $type)
                    <tr>
                        <td colspan="7" style="padding:0">
                            <form method="POST" action="{{ route('document-types.update', $type) }}" class="row-form">
                                @csrf @method('PUT')
                                <input name="name" value="{{ $type->name }}" required style="flex:1">
                                <input type="number" min="0" max="999" name="sort" value="{{ $type->sort }}" style="width:80px">
                                <label style="display:flex;align-items:center;gap:6px;font-weight:500"><input type="checkbox" name="is_required" value="1" style="width:auto" @checked($type->is_required)> Req'd</label>
                                <label style="display:flex;align-items:center;gap:6px;font-weight:500"><input type="checkbox" name="requires_expiry" value="1" style="width:auto" @checked($type->requires_expiry)> Expiry</label>
                                <label style="display:flex;align-items:center;gap:6px;font-weight:500"><input type="checkbox" name="is_active" value="1" style="width:auto" @checked($type->is_active)> Active</label>
                                <span class="badge gray">{{ $type->documents_count }} files</span>
                                <button class="btn sm primary">Save</button>
                            </form>
                            <form method="POST" action="{{ route('document-types.destroy', $type) }}" onsubmit="return confirm('Delete this document type?')" style="padding:0 18px 13px">
                                @csrf @method('DELETE')
                                <button class="btn sm danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="muted">No document types yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $types->links() }}
    </div>
@endsection
