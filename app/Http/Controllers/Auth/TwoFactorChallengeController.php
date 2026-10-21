<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\EmailOtp;
use App\Support\Totp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TwoFactorChallengeController extends Controller
{
    private const MAX_ATTEMPTS = 6;

    private const DECAY_SECONDS = 300;

    public function show(Request $request): View|RedirectResponse
    {
        $user = $this->pendingUser($request);

        if (! $user) {
            return redirect()->route('login');
        }

        $email = (string) $user->email;
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
        $masked = substr($local, 0, 1).'***@'.$domain;

        return view('auth.two-factor', [
            'mode' => $user->hasTwoFactorEnabled() ? 'totp' : 'email',
            'maskedEmail' => $masked,
        ]);
    }

    public function resend(Request $request): RedirectResponse
    {
        $user = $this->pendingUser($request);

        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->hasTwoFactorEnabled()) {
            return back()->withErrors(['code' => 'Use your authenticator app or a recovery code.']);
        }

        $throttleKey = 'otp-resend|'.$user->id;

        if (RateLimiter::tooManyAttempts($throttleKey, 1)) {
            return back()->withErrors([
                'code' => 'Please wait '.RateLimiter::availableIn($throttleKey).' seconds before requesting another code.',
            ]);
        }

        try {
            EmailOtp::issue($user, $request->ip());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors(['code' => 'We could not send another code. Try again shortly.']);
        }

        RateLimiter::hit($throttleKey, 60);
        $request->session()->put('2fa.expires_at', now()->addMinutes(EmailOtp::TTL_MINUTES + 5)->timestamp);

        return back()->with('status', 'A new code has been emailed to you.');
    }

    public function verify(Request $request): RedirectResponse
    {
        $userId = $this->pendingUserId($request);

        if (! $userId) {
            return redirect()->route('login');
        }

        $request->validate(['code' => ['required', 'string', 'max:20']]);

        $throttleKey = '2fa|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            return back()->withErrors([
                'code' => 'Too many attempts. Try again in '.RateLimiter::availableIn($throttleKey).' seconds.',
            ]);
        }

        $user = User::find($userId);

        if (! $user || ! $user->is_active) {
            $this->clearChallenge($request);

            return redirect()->route('login')->withErrors(['email' => 'Your session expired. Sign in again.']);
        }

        $code = trim($request->string('code')->toString());

        if ($user->hasTwoFactorEnabled()) {
            $valid = Totp::verify((string) $user->two_factor_secret, $code)
                || $user->consumeRecoveryCode($code);
        } elseif (config('auth.email_otp')) {
            $valid = EmailOtp::verify($user, $code);
        } else {
            $valid = false;
        }

        if (! $valid) {
            RateLimiter::hit($throttleKey, self::DECAY_SECONDS);

            return back()->withErrors(['code' => 'Invalid or expired code.']);
        }

        RateLimiter::clear($throttleKey);

        $remember = (bool) $request->session()->pull('2fa.remember', false);
        $this->clearChallenge($request);

        Auth::login($user, $remember);
        $request->session()->regenerate();

        $user->forceFill(['last_login_at' => now()])->save();
        AuditLog::record('login', 'Signed in (code verified): '.$user->email, $user);

        return redirect()->intended(route('dashboard'));
    }

    private function pendingUser(Request $request): ?User
    {
        $id = $this->pendingUserId($request);

        if (! $id) {
            return null;
        }

        $user = User::find($id);

        if (! $user || ! $user->is_active) {
            $this->clearChallenge($request);

            return null;
        }

        return $user;
    }

    private function pendingUserId(Request $request): ?int
    {
        $expires = (int) $request->session()->get('2fa.expires_at', 0);

        if ($expires > 0 && $expires < now()->timestamp) {
            $this->clearChallenge($request);

            return null;
        }

        $id = $request->session()->get('2fa.user_id');

        return $id ? (int) $id : null;
    }

    private function clearChallenge(Request $request): void
    {
        $request->session()->forget(['2fa.user_id', '2fa.remember', '2fa.mode', '2fa.expires_at']);
    }
}
