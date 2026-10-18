<x-layouts.auth title="Sign in">
    <p class="eyebrow">Employee access</p>
    <h2>Welcome back</h2>
    <p class="form-intro">Use the account provided by your administrator.</p>

    @if ($errors->any())
        <div class="form-alert" role="alert">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('login.store') }}" class="auth-form">
        @csrf
        <label for="email">Email address</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus>

        <div class="label-row"><label for="password">Password</label><span>Case sensitive</span></div>
        <input id="password" name="password" type="password" autocomplete="current-password" required>

        <label class="check-row"><input name="remember" type="checkbox" value="1"> Keep me signed in on this device</label>
        <button type="submit" class="primary-button">Sign in <span aria-hidden="true">→</span></button>
    </form>
    <p class="auth-help">Need account help? Contact your administrator directly.</p>
</x-layouts.auth>
