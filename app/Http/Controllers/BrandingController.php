<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Support\Branding;
use App\Support\LogoImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BrandingController extends Controller
{
    public function edit(): View
    {
        return view('settings.branding', [
            'logoUrl' => Branding::url(),
            'logoPath' => Branding::path(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'logo' => ['required', 'file', 'max:2048', 'mimes:png,jpg,jpeg,webp,svg'],
        ], [
            'logo.max' => 'The logo must be 2 MB or smaller.',
            'logo.mimes' => 'Upload a PNG, JPG, WebP or SVG file.',
        ]);

        $file = $request->file('logo');
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'png');

        if ($extension === 'svg') {
            $svg = (string) file_get_contents($file->getRealPath());

            if (preg_match('/<script|javascript:|\son[a-z]+\s*=/i', $svg)) {
                throw ValidationException::withMessages([
                    'logo' => 'That SVG contains script content. Please export a clean SVG or upload a PNG instead.',
                ]);
            }
        }

        $previous = Branding::path();
        $disk = Storage::disk('public');
        $stored = $file->storeAs('branding', 'logo-'.Str::random(12).'.'.$extension, 'public');

        if ($extension !== 'svg') {
            $png = 'branding/logo-'.Str::random(12).'.png';

            if (LogoImage::toTransparentPng($disk->path($stored), $disk->path($png))) {
                $disk->delete($stored);
                $stored = $png;
            }
        }

        Branding::set($stored);

        if ($previous && $previous !== $stored) {
            $disk->delete($previous);
        }

        AuditLog::record('branding', 'Logo updated: '.$stored);

        return back()->with('ok', 'Logo saved. It now appears on the sign-in page, sidebar, favicon and payslips.');
    }

    public function destroy(): RedirectResponse
    {
        $path = Branding::path();

        Branding::clear();

        if ($path) {
            Storage::disk('public')->delete($path);
        }

        AuditLog::record('branding', 'Logo removed; default branding restored.');

        return back()->with('ok', 'Logo removed. Default branding restored.');
    }

    public function logo(): BinaryFileResponse
    {
        $path = Branding::path();

        abort_unless($path, 404);

        $disk = Storage::disk('public');

        abort_unless($disk->exists($path), 404);

        return response()->file($disk->path($path), [
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}
