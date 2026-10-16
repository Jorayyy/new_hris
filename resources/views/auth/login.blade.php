<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Time Clock · {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('app.css') }}">
</head>
<body class="kiosk-body">
@php
    $icons = [
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-6 8-6s8 2 8 6"/></svg>',
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M7 4h10v4a5 5 0 0 1-10 0z"/><path d="M7 6H4v2a3 3 0 0 0 3 3M17 6h3v2a3 3 0 0 1-3 3"/><path d="M9 20h6M12 13v7"/></svg>',
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="8"/><path d="M12 8v4l3 2"/></svg>',
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l2.6 5.3 5.9.9-4.3 4.1 1 5.8L12 16.9 6.8 19.2l1-5.8L3.5 9.2l5.9-.9z"/></svg>',
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="3"/></svg>',
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="7" width="18" height="12" rx="2"/><path d="M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2"/></svg>',
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>',
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M13 3L4 14h6l-1 7 9-11h-6z"/></svg>',
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/></svg>',
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="7" width="13" height="10" rx="2"/><path d="M16 10l5-3v10l-5-3z"/></svg>',
    ];
@endphp

<div class="kiosk-bg" aria-hidden="true">
    @for($i = 0; $i < 30; $i++)
        <span class="kiosk-ico">{!! $icons[$i % count($icons)] !!}</span>
    @endfor
</div>

<main class="kiosk-card">
    <div class="kiosk-head">
        <span class="kiosk-logo">HR</span>
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
    })();
</script>
</body>
</html>
