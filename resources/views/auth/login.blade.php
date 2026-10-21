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

    <div class="kiosk-clock" id="clk" data-server-now="{{ now()->toIso8601String() }}"><span id="clk-hm">{{ now()->format('H:i') }}</span><span class="kiosk-secs" id="clk-s">{{ now()->format('s') }}</span></div>
    <div class="kiosk-date" id="clk-date">{{ now()->format('l, F j, Y') }}</div>

    <div class="kiosk-toast" id="client-toast" hidden></div>

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
        <input type="hidden" name="punch_at" id="punch-at" value="">
        <input type="hidden" name="request_id" id="punch-request-id" value="">
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
        var clock = document.getElementById('clk');
        var hm = document.getElementById('clk-hm');
        var secs = document.getElementById('clk-s');
        var date = document.getElementById('clk-date');
        var pad = function (n) { return String(n).padStart(2, '0'); };

        // Follow the server clock so a phone with the wrong time still punches
        // on the correct DTR minute.
        var offset = 0;
        if (clock && clock.dataset.serverNow) {
            var parsed = Date.parse(clock.dataset.serverNow);
            if (!isNaN(parsed)) { offset = parsed - Date.now(); }
        }
        function now() { return new Date(Date.now() + offset); }

        function tick() {
            var t = now();
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

        var toast = document.getElementById('client-toast');
        var buttons = punchView.querySelectorAll('button[name="punch"]');
        var busy = false;
        var punch = null;
        var attempt = 0;
        var hideTimer = null;

        function show(kind, message) {
            clearTimeout(hideTimer);
            toast.hidden = false;
            toast.className = 'kiosk-toast' + (kind ? ' ' + kind : '');
            toast.textContent = message;
        }
        function hideLater(ms) {
            clearTimeout(hideTimer);
            hideTimer = setTimeout(function () { toast.hidden = true; }, ms);
        }
        function setBusy(state) {
            busy = state;
            Array.prototype.forEach.call(buttons, function (b) { b.disabled = state; });
        }
        function newId() {
            if (window.crypto && crypto.randomUUID) { return crypto.randomUUID(); }
            return Date.now().toString(36) + '-' + Math.random().toString(36).slice(2);
        }

        punchView.addEventListener('click', function (e) {
            var btn = e.target.closest('button[name="punch"]');
            if (btn) { punch = btn.value; }
        });

        punchView.addEventListener('submit', function (e) {
            if (!punch) { return; }
            e.preventDefault();
            if (busy) { return; }

            punchView.querySelector('[name="punch_at"]').value = now().toISOString();
            punchView.querySelector('[name="request_id"]').value = newId();

            var body = new FormData(punchView);
            body.set('punch', punch);

            attempt = 0;
            setBusy(true);
            show('', 'Recording your punch…');
            send(body);
        });

        function send(body) {
            fetch(punchView.action, {
                method: 'POST',
                body: body,
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            }).then(function (res) {
                return res.json().catch(function () { return {}; }).then(function (data) {
                    return { res: res, data: data };
                });
            }).then(function (r) {
                if (r.res.ok && r.data.ok !== false) {
                    show('ok', r.data.message || 'Punch recorded.');
                    punchView.reset();
                    punch = null;
                    setBusy(false);
                    hideLater(9000);
                    return;
                }
                if (r.res.status === 419) {
                    location.reload();
                    return;
                }
                if (r.res.status === 422 || r.res.status === 403 || r.res.status === 409) {
                    show('err', r.data.message || 'Punch was not recorded.');
                    setBusy(false);
                    return;
                }
                retry(body);
            }).catch(function () {
                retry(body);
            });
        }

        // Hold the punch (with its original tap time) while the server is busy,
        // instead of failing. Retries back off so the database is not swamped.
        function retry(body) {
            attempt++;
            if (attempt > 8) {
                show('err', 'The time clock is busy. Please tap your punch again.');
                setBusy(false);
                return;
            }
            var wait = Math.min(8000, 400 * Math.pow(2, attempt - 1));
            wait += Math.floor(Math.random() * 400);
            show('', 'Time clock is busy — holding your punch for ' + Math.ceil(wait / 1000) + 's…');
            setTimeout(function () { send(body); }, wait);
        }
    })();
</script>
</body>
</html>
