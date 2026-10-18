<x-filament-panels::page>
    <x-filament::section>
        <form wire:submit="run" class="space-y-5">
            {{ $this->form }}
            <div class="flex flex-wrap gap-3">
                <x-filament::button type="submit">Run report</x-filament::button>
                <x-filament::button tag="a" color="gray" :href="$this->exportUrl('csv')">Download CSV</x-filament::button>
                <x-filament::button tag="a" color="gray" :href="$this->exportUrl('xlsx')">Download Excel</x-filament::button>
            </div>
        </form>
    </x-filament::section>

    <x-filament::section>
        <div class="overflow-x-auto">
            <table class="w-full text-sm" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr class="border-b text-left">
                        <th style="padding: .85rem 1rem; text-align: left;">Date</th><th style="padding: .85rem 1rem; text-align: left;">Employee</th><th style="padding: .85rem 1rem; text-align: left;">Department</th>
                        <th style="padding: .85rem 1rem; text-align: left;">Clock in</th><th style="padding: .85rem 1rem; text-align: left;">Clock out</th><th style="padding: .85rem 1rem; text-align: left;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->records as $record)
                        <tr class="border-b">
                            <td style="padding: .85rem 1rem; white-space: nowrap;">{{ $record->work_date->format('M d, Y') }}</td>
                            <td style="padding: .85rem 1rem; min-width: 10rem;">{{ $record->employeeProfile->user->name }}<br><span class="text-xs text-gray-500">{{ $record->employeeProfile->employee_number }}</span></td>
                            <td style="padding: .85rem 1rem;">{{ $record->employeeProfile->department?->name ?? '—' }}</td>
                            <td style="padding: .85rem 1rem; white-space: nowrap;">{{ $record->clock_in_at?->setTimezone(app(\App\Domain\Settings\ApplicationSettings::class)->timezone())->format('M d, Y g:i A') ?? '—' }}</td>
                            <td style="padding: .85rem 1rem; white-space: nowrap;">{{ $record->clock_out_at?->setTimezone(app(\App\Domain\Settings\ApplicationSettings::class)->timezone())->format('M d, Y g:i A') ?? '—' }}</td>
                            <td style="padding: .85rem 1rem;">{{ str($record->status->value)->replace('_', ' ')->title() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="p-8 text-center text-gray-500">No attendance records match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-5">{{ $this->records->links() }}</div>
    </x-filament::section>
</x-filament-panels::page>
