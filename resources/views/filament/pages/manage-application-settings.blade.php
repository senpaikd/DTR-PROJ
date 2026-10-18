<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Time and correction rules</x-slot>
        <x-slot name="description">
            A timezone change affects how future shifts and screen values are interpreted. Historical attendance instants remain stored in UTC and are not rewritten.
        </x-slot>

        <form wire:submit="save" class="space-y-6">
            {{ $this->form }}

            <x-filament::button type="submit">Save settings</x-filament::button>
        </form>
    </x-filament::section>
</x-filament-panels::page>
