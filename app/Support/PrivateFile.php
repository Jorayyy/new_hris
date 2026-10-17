<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams files kept on the private disk. Callers must authorize first —
 * the files live outside the web root, so nothing is reachable by URL.
 */
class PrivateFile
{
    public static function download(?string $path, string $baseName): StreamedResponse
    {
        $disk = Storage::disk('local');

        abort_unless(is_string($path) && $path !== '' && $disk->exists($path), 404, 'File not found.');

        $extension = pathinfo($path, PATHINFO_EXTENSION);

        return $disk->download($path, $baseName.($extension !== '' ? '.'.$extension : ''));
    }
}
