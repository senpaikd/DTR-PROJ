<x-layouts.auth title="Create your password">
    <p class="eyebrow">First sign-in</p>
    <h2>Create your private password</h2>
    <p class="form-intro">Replace the temporary password before continuing to your time record.</p>

    @if ($errors->any())
        <div class="form-alert" role="alert"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <form method="POST" action="{{ route('first-login.password.update') }}" class="auth-form">
        @csrf
        @method('PUT')
        <label for="current_password">Temporary password</label>
        <input id="current_password" name="current_password" type="password" autocomplete="current-password" required autofocus>
        <label for="password">New password</label>
        <input id="password" name="password" type="password" autocomplete="new-password" required aria-describedby="password-help">
        <p id="password-help" class="field-help">At least 12 characters with upper and lowercase letters, a number, and a symbol.</p>
        <label for="password_confirmation">Confirm new password</label>
        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
        <button type="submit" class="primary-button">Save password <span aria-hidden="true">→</span></button>
    </form>
</x-layouts.auth>
