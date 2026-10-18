<x-layouts.employee title="Request a correction">
    <section class="page-heading">
        <div><p class="eyebrow">Attendance support</p><h1>Request a correction</h1><p>Tell the administrator what the official record should show and why.</p></div>
    </section>
    <section class="panel form-panel">
        <form method="POST" action="{{ route('corrections.store') }}" class="correction-form">
            @csrf
            @if ($record)
                <input type="hidden" name="attendance_record_id" value="{{ $record->id }}">
            @endif
            <div>
                <label for="work_date">Work date</label>
                <input id="work_date" name="work_date" type="date" required value="{{ old('work_date', $record?->work_date->toDateString() ?? request('date')) }}">
            </div>
            <div class="form-grid">
                <div><label for="proposed_clock_in_at">Proposed clock-in</label><input id="proposed_clock_in_at" name="proposed_clock_in_at" type="datetime-local" value="{{ old('proposed_clock_in_at', $record?->clock_in_at?->setTimezone($record->timezone)->format('Y-m-d\TH:i')) }}"></div>
                <div><label for="proposed_clock_out_at">Proposed clock-out</label><input id="proposed_clock_out_at" name="proposed_clock_out_at" type="datetime-local" value="{{ old('proposed_clock_out_at', $record?->clock_out_at?->setTimezone($record->timezone)->format('Y-m-d\TH:i')) }}"></div>
            </div>
            <div><label for="reason">Reason</label><textarea id="reason" name="reason" rows="5" required minlength="10" maxlength="2000" placeholder="Explain what happened and what should be corrected.">{{ old('reason') }}</textarea></div>
            <div class="form-actions"><a href="{{ route('corrections.index') }}">Cancel</a><button class="primary-button compact" type="submit">Submit request</button></div>
        </form>
    </section>
</x-layouts.employee>
