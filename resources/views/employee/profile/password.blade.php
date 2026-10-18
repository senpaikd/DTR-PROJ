<x-layouts.employee title="Change password">
    <section class="page-heading">
        <div>
            <p class="eyebrow">ACCOUNT SECURITY</p>
            <h1>Change password</h1>
            <p>Confirm your current password, then choose a unique replacement with at least 12 characters.</p>
        </div>
        <a class="secondary-button compact" href="{{ route('profile.edit') }}">Back to profile</a>
    </section>

    <section class="panel form-panel">
        <form method="POST" action="{{ route('profile.password.update') }}" class="correction-form">
            @csrf
            @method('PUT')

            <label>
                <span>Current password</span>
                <input type="password" name="current_password" autocomplete="current-password" required autofocus>
                @error('current_password') <small class="form-alert">{{ $message }}</small> @enderror
            </label>

            <label>
                <span>New password</span>
                <input type="password" name="password" autocomplete="new-password" required>
                <small class="field-help">Use at least 12 characters with uppercase, lowercase, a number, and a symbol.</small>
                @error('password') <small class="form-alert">{{ $message }}</small> @enderror
            </label>

            <label>
                <span>Confirm new password</span>
                <input type="password" name="password_confirmation" autocomplete="new-password" required>
            </label>

            <div class="form-actions">
                <a href="{{ route('profile.edit') }}">Cancel</a>
                <button class="primary-button" type="submit">Change password</button>
            </div>
        </form>
    </section>
</x-layouts.employee>
