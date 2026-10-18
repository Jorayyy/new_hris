<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset password · {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('app.css') }}">
</head>
<body class="kiosk-body">
<main class="kiosk-card">
    <div class="kiosk-head">
        <span class="kiosk-logo">HR</span>
        <div>
            <div class="kiosk-title">{{ config('app.name') }}</div>
            <div class="kiosk-sub">Reset password</div>
        </div>
    </div>

    @if(session('status'))
        <div class="kiosk-toast ok">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="kiosk-toast err">{{ $errors->first() }}</div>
    @endif

    <div class="kiosk-staff">
        <div class="kiosk-sub">Enter your work email and we will send a reset link.</div>
        <form method="POST" action="{{ route('password.email') }}">
            @csrf
            <div class="field">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
            </div>
            <button class="btn primary" style="width:100%;justify-content:center">Send reset link</button>
        </form>
        <a class="kiosk-link" href="{{ route('login') }}">Back to sign in</a>
    </div>
</main>
</body>
</html>
