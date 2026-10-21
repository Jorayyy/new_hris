@extends('layouts.app')

@section('title', 'Branding')
@section('content')
    <div class="card" style="max-width:820px">
        <div class="card-head"><h2>Company logo</h2></div>
        <div class="card-body">
            <p class="muted small mb-0">
                This logo replaces the default branding on the sign-in card, the sidebar, the browser favicon, payslip PDFs and sign-in emails. Only a super admin can change it.
            </p>

            <div style="margin:18px 0;padding:22px;border-radius:12px;background:#0f3b36;border:1px solid rgba(127,184,178,.18);display:flex;align-items:center;justify-content:center;min-height:130px">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="Current logo" style="max-height:96px;max-width:100%;object-fit:contain;background:#fff;padding:14px 18px;border-radius:12px">
                @else
                    <div style="text-align:center;color:#8fb9b4;font-size:13px">
                        <span style="display:inline-grid;place-items:center;width:44px;height:44px;border-radius:10px;background:#e7f2f0;color:#0b2b28;font-weight:800;font-size:13px;margin-bottom:8px">HR</span>
                        <div>No logo uploaded — default branding is in use.</div>
                    </div>
                @endif
            </div>

            <form method="POST" action="{{ route('branding.update') }}" enctype="multipart/form-data" class="filters" style="align-items:flex-end">
                @csrf
                <div class="field" style="min-width:260px">
                    <label for="logo">Upload logo</label>
                    <input id="logo" type="file" name="logo" accept=".png,.jpg,.jpeg,.webp,.svg,image/png,image/jpeg,image/webp,image/svg+xml" required>
                </div>
                <button class="btn primary"><x-icon name="upload" /> Save logo</button>
            </form>

            <p class="small muted" style="margin:10px 0 0">
                PNG, JPG, WebP or SVG · up to 2 MB. White backgrounds on PNG/JPG/WebP are removed automatically,
                so the logo sits cleanly on a white plate in the dark sidebar and on the sign-in card. The logo is always scaled to fit, never cropped.
            </p>

            @if($logoPath)
                <form method="POST" action="{{ route('branding.destroy') }}" style="margin-top:18px" onsubmit="return confirm('Remove the logo and restore default branding?')">
                    @csrf
                    @method('DELETE')
                    <button class="btn sm danger">Remove logo</button>
                </form>
            @endif
        </div>
    </div>
@endsection
