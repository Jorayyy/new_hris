<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Time Clock · {{ config('app.name') }}</title>
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
            <div class="kiosk-sub">Time Clock</div>
        </div>
    </div>

    <div class="kiosk-clock"><span id="clk-hm">{{ now()->format('H:i') }}</span><span class="kiosk-secs" id="clk-s">{{ now()->format('s') }}</span></div>
    <div class="kiosk-date" id="clk-date">{{ now()->format('l, F j, Y') }}</div>

    @if(session('punch_ok'))
        <div class="kiosk-toast ok">{{ session('punch_ok') }}</div>
    @endif
    @if(session('login_status'))
        <div class="kiosk-toast ok">{{ session('login_status') }}</div>
    @endif
    @if(session('punch_error'))
        <div class="kiosk-toast err">{{ session('punch_error') }}</div>
    @endif

    <form method="POST" action="{{ route('time-clock.punch') }}" class="kiosk-form" autocomplete="off" id="punch-view" @if($errors->any()) hidden @endif>
        @csrf
        <div class="kiosk-grid-inputs">
            <div>
                <label>Employee no.</label>
                <input name="employee_no" value="{{ old('employee_no') }}" placeholder="E-0032" required>
            </div>
            <div>
                <label>PIN</label>
                <input name="pin" type="password" inputmode="numeric" maxlength="4" placeholder="••••" required>
            </div>
        </div>
        <div class="kiosk-punches">
            <button name="punch" value="in" class="punch punch-dark">IN</button>
            <button name="punch" value="break1_out" class="punch">1st Break Out</button>
            <button name="punch" value="break1_in" class="punch">1st Break In</button>
            <button name="punch" value="lunch_out" class="punch">Lunch Out</button>
            <button name="punch" value="lunch_in" class="punch">Lunch In</button>
            <button name="punch" value="break2_out" class="punch">2nd Break Out</button>
            <button name="punch" value="break2_in" class="punch">2nd Break In</button>
            <button name="punch" value="out" class="punch punch-dark">OUT</button>
        </div>
        <button type="button" class="kiosk-link" id="staff-toggle">Staff sign in</button>
    </form>

    <form method="POST" action="{{ route('login') }}" class="kiosk-staff" id="staff-view" @if(! $errors->any()) hidden @endif>
        @csrf
        <div class="kiosk-sub">Staff sign in</div>
        @if($errors->any())
            <div class="kiosk-toast err">{{ $errors->first() }}</div>
        @endif
        <div class="field">
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required>
        </div>
        <div class="field">
            <label for="password">Password</label>
            <input id="password" type="password" name="password" required>
        </div>
        <label class="kiosk-remember"><input type="checkbox" name="remember" value="1"> Remember me</label>
        <button type="submit" class="btn primary" style="width:100%;justify-content:center">Sign in</button>
        <a class="kiosk-link" href="{{ route('password.request') }}">Forgot password?</a>
        <button type="button" class="kiosk-link" id="staff-back">Back to time clock</button>
    </form>
</main>

<script>
    (function () {
        var hm = document.getElementById('clk-hm');
        var secs = document.getElementById('clk-s');
        var date = document.getElementById('clk-date');
        var pad = function (n) { return String(n).padStart(2, '0'); };
        function tick() {
            var t = new Date();
            hm.textContent = pad(t.getHours()) + ':' + pad(t.getMinutes());
            secs.textContent = pad(t.getSeconds());
            date.textContent = t.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        }
        tick();
        setInterval(tick, 1000);

        var toggle = document.getElementById('staff-toggle');
        var back = document.getElementById('staff-back');
        var punchView = document.getElementById('punch-view');
        var staffView = document.getElementById('staff-view');
        toggle.addEventListener('click', function () { punchView.hidden = true; staffView.hidden = false; });
        back.addEventListener('click', function () { staffView.hidden = true; punchView.hidden = false; });

        // Keep the kiosk session (and its CSRF token) alive between punches.
        setInterval(function () { fetch(location.pathname, { credentials: 'same-origin' }); }, 20 * 60 * 1000);
    })();
</script>
</body>
</html>
