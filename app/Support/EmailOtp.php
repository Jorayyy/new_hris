<?php

namespace App\Support;

use App\Models\LoginOtp;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

final class EmailOtp
{
    public const TTL_MINUTES = 10;

    public const MAX_ATTEMPTS = 5;

    public const MAX_PER_WINDOW = 5;

    public const WINDOW_MINUTES = 15;

    public static function issue(User $user, ?string $ip = null, bool $send = true, ?string $fixedCode = null): string
    {
        $recent = LoginOtp::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subMinutes(self::WINDOW_MINUTES))
            ->count();

        if ($recent >= self::MAX_PER_WINDOW) {
            throw ValidationException::withMessages([
                'email' => 'Too many sign-in codes requested. Try again in a few minutes.',
            ]);
        }

        LoginOtp::where('user_id', $user->id)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        $code = $fixedCode ?? (string) random_int(100000, 999999);

        LoginOtp::create([
            'user_id' => $user->id,
            'code_hash' => Hash::make($code),
            'ip' => $ip,
            'expires_at' => now()->addMinutes(self::TTL_MINUTES),
        ]);

        if ($send) {
            Mail::html(
                view('mail.sign-in-code', [
                    'code' => $code,
                    'name' => $user->name,
                    'ttl' => self::TTL_MINUTES,
                    'logo' => Branding::url(),
                ])->render(),
                fn ($message) => $message->to($user->email)->subject(config('app.name').' sign-in code')
            );
        }

        return $code;
    }

    public static function verify(User $user, string $code): bool
    {
        $code = preg_replace('/\D/', '', $code) ?? '';

        $otp = LoginOtp::where('user_id', $user->id)
            ->whereNull('consumed_at')
            ->latest('id')
            ->first();

        if (! $otp || $otp->expires_at->isPast() || $otp->attempts >= self::MAX_ATTEMPTS) {
            return false;
        }

        if (strlen($code) !== 6 || ! Hash::check($code, $otp->code_hash)) {
            $otp->increment('attempts');

            return false;
        }

        $otp->forceFill(['consumed_at' => now()])->save();

        return true;
    }
}
