<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Support\EmailOtp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    private const DECAY_SECONDS = 300;

    public function show(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $throttleKey = Str::lower($credentials['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            throw ValidationException::withMessages([
                'email' => 'Too many failed attempts. Try again in '.RateLimiter::availableIn($throttleKey).' seconds.',
            ]);
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, self::DECAY_SECONDS);

            return back()->withErrors(['email' => 'Invalid credentials.'])->onlyInput('email');
        }

        RateLimiter::clear($throttleKey);

        $user = $request->user();

        if (! $user->is_active) {
            Auth::logout();

            return back()->withErrors(['email' => 'Your account is inactive. Contact HR.']);
        }

        if ($user->hasTwoFactorEnabled()) {
            $remember = $request->boolean('remember');

            Auth::logout();
            $request->session()->regenerate();
            $request->session()->put([
                '2fa.user_id' => $user->id,
                '2fa.remember' => $remember,
                '2fa.expires_at' => now()->addMinutes(10)->timestamp,
            ]);

            return redirect()->route('two-factor.challenge');
        }

        if ($user->requiresEmailOtp()) {
            try {
                EmailOtp::issue($user, $request->ip());
            } catch (ValidationException $e) {
                Auth::logout();

                return back()->withErrors($e->errors())->onlyInput('email');
            } catch (\Throwable $e) {
                Auth::logout();
                report($e);

                return back()->withErrors([
                    'email' => 'We could not send your sign-in code. Try again or contact your administrator.',
                ])->onlyInput('email');
            }

            $remember = $request->boolean('remember');

            Auth::logout();
            $request->session()->regenerate();
            $request->session()->put([
                '2fa.user_id' => $user->id,
                '2fa.remember' => $remember,
                '2fa.mode' => 'email',
                '2fa.expires_at' => now()->addMinutes(EmailOtp::TTL_MINUTES + 5)->timestamp,
            ]);

            AuditLog::record('login', 'Password verified, code emailed: '.$user->email, $user);

            return redirect()->route('two-factor.challenge');
        }

        $request->session()->regenerate();

        $user->forceFill(['last_login_at' => now()])->save();
        AuditLog::record('login', 'Signed in: '.$user->email, $user);

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user) {
            AuditLog::record('logout', 'Signed out: '.$user->email, $user);
        }

        Auth::logout();
        $request->session()->forget(['2fa.user_id', '2fa.remember', '2fa.mode', '2fa.expires_at']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
