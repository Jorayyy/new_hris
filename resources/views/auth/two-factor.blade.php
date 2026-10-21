<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verification · {{ config('app.name') }}</title>
    <link rel="preload" as="font" type="font/woff2" href="{{ asset('fonts/inter-latin-wght-normal.woff2') }}" crossorigin>
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
            <div class="kiosk-sub">{{ $mode === 'email' ? 'Email verification' : 'Two-factor authentication' }}</div>
        </div>
    </div>

    @if(session('status'))
        <div class="kiosk-toast ok">{{ session('status') }}</div>
    @endif

    @if($errors->any())
        <div class="kiosk-toast err">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('two-factor.verify') }}" class="kiosk-staff" autocomplete="off">
        @csrf
        <p class="kiosk-sub">
            @if($mode === 'email')
                We emailed a 6-digit code to <strong>{{ $maskedEmail }}</strong>. It expires in 10 minutes.
            @else
                Enter the 6-digit code from your authenticator app, or one of your recovery codes.
            @endif
        </p>
        <div class="field">
            <label for="code">Authentication code</label>
            <input id="code" name="code" inputmode="numeric" autocomplete="one-time-code" autofocus required>
        </div>
        <button type="submit" class="btn primary" style="width:100%;justify-content:center">Verify</button>
    </form>

    @if($mode === 'email')
        <form method="POST" action="{{ route('two-factor.resend') }}">
            @csrf
            <button type="submit" class="kiosk-link">Resend code</button>
        </form>
    @endif

    <a class="kiosk-link" href="{{ route('login') }}">Back to sign in</a>
</main>
</body>
</html>
