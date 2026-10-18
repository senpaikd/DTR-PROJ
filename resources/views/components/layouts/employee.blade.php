<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'My DTR' }} · Oras</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="employee-shell">
<header class="site-header">
    <a class="brand" href="{{ route('dashboard') }}"><span aria-hidden="true">O</span> ORAS</a>
    <nav aria-label="Main navigation"><a href="{{ route('dashboard') }}" @class(['active' => request()->routeIs('dashboard')])>Today</a><a href="{{ route('attendance.index') }}" @class(['active' => request()->routeIs('attendance.*')])>Records</a><a href="{{ route('corrections.index') }}" @class(['active' => request()->routeIs('corrections.*')])>Requests</a></nav>
    <div class="header-account"><a href="{{ route('profile.edit') }}">{{ auth()->user()->name }}</a><form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Sign out</button></form></div>
</header>
@if (session('status'))<div class="toast" role="status">{{ session('status') }}</div>@endif
@if ($errors->any())<div class="toast error" role="alert">{{ $errors->first() }}</div>@endif
<main class="employee-main">{{ $slot }}</main>
<footer class="site-footer"><span>Oras Daily Time Record</span><span>Times shown in the organization timezone</span></footer>
</body>
</html>
