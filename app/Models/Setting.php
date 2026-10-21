<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

#[Fillable(['key', 'value'])]
class Setting extends Model
{
    public static function get(string $key, mixed $default = null): mixed
    {
        $cached = Cache::rememberForever(self::cacheKey($key), fn () => [
            static::query()->where('key', $key)->value('value'),
        ]);

        return $cached[0] ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => is_scalar($value) || $value === null ? (string) $value : json_encode($value)]);

        Cache::forget(self::cacheKey($key));
    }

    public static function forget(string $key): void
    {
        Cache::forget(self::cacheKey($key));
    }

    private static function cacheKey(string $key): string
    {
        return 'setting:'.$key;
    }
}
