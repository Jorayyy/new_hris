<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['cidr', 'label', 'is_active'])]
class TimeClockIp extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** Whether the kiosk may be punched from this IP. No active rules = unrestricted. */
    public static function allows(?string $ip): bool
    {
        $rules = static::query()->where('is_active', true)->pluck('cidr');

        if ($rules->isEmpty()) {
            return true;
        }

        if (! $ip) {
            return false;
        }

        return $rules->contains(fn ($cidr) => static::matches($ip, $cidr));
    }

    public static function matches(string $ip, string $cidr): bool
    {
        $cidr = trim($cidr);
        $bits = null;

        if (str_contains($cidr, '/')) {
            [$cidr, $bits] = explode('/', $cidr, 2);
            $bits = (int) $bits;
        }

        $ipBin = @inet_pton($ip);
        $subnetBin = @inet_pton($cidr);

        if ($ipBin === false || $subnetBin === false || strlen($ipBin) !== strlen($subnetBin)) {
            return false;
        }

        if ($bits === null) {
            return $ipBin === $subnetBin;
        }

        $maxBits = strlen($ipBin) * 8;
        if ($bits < 0 || $bits > $maxBits) {
            return false;
        }

        $wholeBytes = intdiv($bits, 8);
        $remainder = $bits % 8;

        if ($wholeBytes > 0 && substr($ipBin, 0, $wholeBytes) !== substr($subnetBin, 0, $wholeBytes)) {
            return false;
        }

        if ($remainder === 0) {
            return true;
        }

        $mask = chr((0xFF << (8 - $remainder)) & 0xFF);

        return (substr($ipBin, $wholeBytes, 1) & $mask) === (substr($subnetBin, $wholeBytes, 1) & $mask);
    }

    public static function isValid(string $value): bool
    {
        $value = trim($value);
        if ($value === '') {
            return false;
        }

        $parts = explode('/', $value, 2);
        $packed = @inet_pton($parts[0]);
        if ($packed === false) {
            return false;
        }

        if (isset($parts[1])) {
            return ctype_digit($parts[1]) && (int) $parts[1] <= strlen($packed) * 8;
        }

        return true;
    }
}
