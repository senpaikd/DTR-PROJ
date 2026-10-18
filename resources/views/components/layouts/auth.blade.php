<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Sign in' }} · Oras DTR</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-shell">
    <main class="auth-wrap">
        <section class="auth-story" aria-labelledby="oras-title">
            <a class="brand" href="{{ route('login') }}" aria-label="Oras DTR home"><span aria-hidden="true">O</span> ORAS</a>
            <div>
                <p class="eyebrow">Daily Time Record</p>
                <h1 id="oras-title">Every workday,<br><em>clearly recorded.</em></h1>
                <p class="story-copy">Secure attendance for teams who value a simple, dependable record of time.</p>
            </div>
            <p class="auth-timezone">Organization time · {{ config('app.timezone') }}</p>
        </section>
        <section class="auth-panel">
            <div class="auth-card">
                {{ $slot }}
            </div>
        </section>
    </main>
</body>
</html>
