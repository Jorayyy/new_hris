<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

final class Branding
{
    public const PATH_KEY = 'branding.logo_path';

    public const VERSION_KEY = 'branding.logo_version';

    private static bool $loaded = false;

    private static ?string $path = null;

    private static ?string $version = null;

    public static function path(): ?string
    {
        self::load();

        return self::$path;
    }

    public static function version(): ?string
    {
        self::load();

        return self::$version;
    }

    public static function url(): ?string
    {
        if (! self::path()) {
            return null;
        }

        $version = self::version();

        return route('branding.logo', $version ? ['v' => $version] : []);
    }

    public static function dataUri(): ?string
    {
        $path = self::path();

        if (! $path) {
            return null;
        }

        $disk = Storage::disk('public');
        $file = $disk->path($path);

        if (! is_file($file)) {
            return null;
        }

        $mime = $disk->mimeType($path) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($file));
    }

    public static function set(string $path): void
    {
        Setting::put(self::PATH_KEY, $path);
        Setting::put(self::VERSION_KEY, (string) time());
        self::$loaded = false;
    }

    public static function clear(): void
    {
        Setting::query()->whereIn('key', [self::PATH_KEY, self::VERSION_KEY])->delete();
        Setting::forget(self::PATH_KEY);
        Setting::forget(self::VERSION_KEY);
        self::$loaded = false;
    }

    private static function load(): void
    {
        if (self::$loaded) {
            return;
        }

        self::$loaded = true;

        $path = Setting::get(self::PATH_KEY);

        if (is_string($path) && $path !== '' && Storage::disk('public')->exists($path)) {
            self::$path = $path;
            self::$version = (string) Setting::get(self::VERSION_KEY, '');
        }
    }
}
