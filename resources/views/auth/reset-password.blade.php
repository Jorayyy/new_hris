<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Set a new password · {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('app.css') }}?v={{ filemtime(public_path('app.css')) }}">
    @include('partials.branding-head')
</head>
<body class="kiosk-body">
@php($brandLogo = \App\Support\Branding::url())
<main class="kiosk-card">
    <div class="kiosk-head">
        <span class="kiosk-logo">
            @if($brandLogo)
                <img src="{{ $brandLogo }}" alt="{{ config('app.name') }}">
            @else
                HR
            @endif
        </span>
        <div>
            <div class="kiosk-title">{{ config('app.name') }}</div>
            <div class="kiosk-sub">Set a new password</div>
        </div>
    </div>

    @if($errors->any())
        <div class="kiosk-toast err">{{ $errors->first() }}</div>
    @endif

    <div class="kiosk-staff">
        <form method="POST" action="{{ route('password.store') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div class="field">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required>
            </div>
            <div class="field">
                <label for="password">New password</label>
                <input id="password" type="password" name="password" minlength="8" required autofocus>
                <p class="muted">At least 8 characters, including a number.</p>
            </div>
            <div class="field">
                <label for="password_confirmation">Confirm new password</label>
                <input id="password_confirmation" type="password" name="password_confirmation" minlength="8" required>
            </div>
            <button class="btn primary" style="width:100%;justify-content:center">Reset password</button>
        </form>
        <a class="kiosk-link" href="{{ route('login') }}">Back to sign in</a>
    </div>
</main>
</body>
</html>
