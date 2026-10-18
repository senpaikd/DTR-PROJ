<x-layouts.employee title="My profile">
    <section class="page-hero compact">
        <div><p class="eyebrow">ACCOUNT</p><h1>My profile</h1><p>Your organization details are protected. You can keep your contact number current here.</p></div>
    </section>

    <section class="panel form-panel">
        <div class="form-grid">
            <label><span>Name</span><input value="{{ auth()->user()->name }}" disabled></label>
            <label><span>Email</span><input value="{{ auth()->user()->email }}" disabled></label>
            <label><span>Employee number</span><input value="{{ $profile->employee_number }}" disabled></label>
            <label><span>Department</span><input value="{{ $profile->department?->name ?? 'Not assigned' }}" disabled></label>
            <label><span>Position</span><input value="{{ $profile->position?->name ?? 'Not assigned' }}" disabled></label>
        </div>

        <form method="POST" action="{{ route('profile.update') }}" class="form-stack">
            @csrf @method('PUT')
            <label><span>Phone number</span><input name="phone" value="{{ old('phone', $profile->phone) }}" maxlength="32" autocomplete="tel"></label>
            <div class="form-actions">
                <a class="secondary-button" href="{{ route('profile.password.edit') }}">Change password</a>
                <button class="primary-button" type="submit">Save contact details</button>
            </div>
        </form>
    </section>
</x-layouts.employee>
