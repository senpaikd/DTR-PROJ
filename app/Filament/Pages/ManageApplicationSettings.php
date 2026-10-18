<?php

namespace App\Filament\Pages;

use App\Domain\Auditing\AuditRecorder;
use App\Domain\Settings\ApplicationSettings;
use App\Models\ApplicationSetting;
use DateTimeZone;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;

class ManageApplicationSettings extends Page
{
    protected static ?string $navigationLabel = 'Settings';

    protected static ?string $title = 'Application settings';

    protected static ?int $navigationSort = 90;

    protected string $view = 'filament.pages.manage-application-settings';

    public ?array $data = [];

    public function mount(ApplicationSettings $settings): void
    {
        $this->form->fill([
            'organization_timezone' => $settings->timezone()->getName(),
            'correction_window_days' => $settings->correctionWindowDays(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('organization_timezone')
                    ->label('Organization timezone')
                    ->options(array_combine(DateTimeZone::listIdentifiers(), DateTimeZone::listIdentifiers()))
                    ->searchable()
                    ->required(),
                TextInput::make('correction_window_days')
                    ->label('Correction request window (days)')
                    ->numeric()
                    ->integer()
                    ->minValue(1)
                    ->maxValue(365)
                    ->required(),
            ])
            ->statePath('data');
    }

    public function save(AuditRecorder $audit): void
    {
        $data = $this->form->getState();
        $before = [
            'organization_timezone' => ApplicationSetting::query()->where('key', 'organization_timezone')->value('value') ?? ApplicationSettings::DEFAULT_TIMEZONE,
            'correction_window_days' => ApplicationSetting::query()->where('key', 'correction_window_days')->value('value') ?? ApplicationSettings::DEFAULT_CORRECTION_WINDOW_DAYS,
        ];

        DB::transaction(function () use ($data, $before, $audit): void {
            foreach ($data as $key => $value) {
                ApplicationSetting::query()->updateOrCreate(
                    ['key' => $key],
                    ['value' => $value, 'updated_by' => auth()->id()],
                );
            }

            $audit->record(auth()->user(), 'application.settings.updated', null, $before, $data);
        });

        Notification::make()->success()->title('Settings saved')->send();
    }
}
