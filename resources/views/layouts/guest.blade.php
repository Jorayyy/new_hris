<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Sign in') · {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('app.css') }}">
</head>
<body>
<div class="auth-wrap">
    <div class="auth-card">
        <h1>{{ config('app.name') }}</h1>
        <p class="sub">Human Resource Information System</p>

        @if($errors->any())
            <div class="alert err">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="field">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input id="password" type="password" name="password" required>
            </div>
            <div class="field">
                <label style="display:flex;align-items:center;gap:8px;font-weight:500">
                    <input type="checkbox" name="remember" value="1" style="width:auto"> Remember me
                </label>
            </div>
            <button type="submit" class="btn primary" style="width:100%;justify-content:center">Sign in</button>
        </form>
    </div>
</div>
</body>
</html>
