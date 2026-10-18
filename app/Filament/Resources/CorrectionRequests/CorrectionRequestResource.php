<?php

namespace App\Filament\Resources\CorrectionRequests;

use App\Domain\Corrections\CorrectionReviewData;
use App\Domain\Corrections\CorrectionReviewService;
use App\Domain\Settings\ApplicationSettings;
use App\Enums\CorrectionRequestStatus;
use App\Filament\Resources\CorrectionRequests\Pages\ListCorrectionRequests;
use App\Models\AttendanceCorrectionRequest;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CorrectionRequestResource extends Resource
{
    protected static ?string $model = AttendanceCorrectionRequest::class;

    protected static ?string $modelLabel = 'correction request';

    protected static ?string $pluralModelLabel = 'correction requests';

    protected static ?string $recordTitleAttribute = 'work_date';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('Requested')->since()->sortable(),
                TextColumn::make('employeeProfile.employee_number')->label('Employee no.')->searchable(),
                TextColumn::make('employeeProfile.user.name')->label('Employee')->searchable(),
                TextColumn::make('work_date')->date()->sortable(),
                TextColumn::make('proposed_clock_in_at')->label('Proposed in')->dateTime()->placeholder('—'),
                TextColumn::make('proposed_clock_out_at')->label('Proposed out')->dateTime()->placeholder('—'),
                TextColumn::make('reason')->limit(45)->wrap(),
                TextColumn::make('status')->badge()->formatStateUsing(fn (CorrectionRequestStatus $state) => str($state->value)->title()),
            ])
            ->filters([
                SelectFilter::make('status')->options(collect(CorrectionRequestStatus::cases())->mapWithKeys(fn ($case) => [$case->value => str($case->value)->title()])->all()),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('approve')
                    ->color('success')
                    ->visible(fn (AttendanceCorrectionRequest $record) => $record->status === CorrectionRequestStatus::Pending)
                    ->schema([
                        DateTimePicker::make('clock_in_at')->label('Final clock in')->seconds(false),
                        DateTimePicker::make('clock_out_at')->label('Final clock out')->seconds(false)->after('clock_in_at'),
                        Textarea::make('explanation')->label('Review note / adjustment explanation')->minLength(10),
                    ])
                    ->fillForm(fn (AttendanceCorrectionRequest $record, ApplicationSettings $settings) => [
                        'clock_in_at' => $record->proposed_clock_in_at?->setTimezone($settings->timezone())->format('Y-m-d H:i:s'),
                        'clock_out_at' => $record->proposed_clock_out_at?->setTimezone($settings->timezone())->format('Y-m-d H:i:s'),
                    ])
                    ->action(function (AttendanceCorrectionRequest $record, array $data, CorrectionReviewService $reviews, ApplicationSettings $settings): void {
                        $toUtc = fn (?string $value) => $value
                            ? CarbonImmutable::parse($value, $settings->timezone())->utc()->format('Y-m-d H:i:s')
                            : null;
                        $reviews->approve(auth()->user(), $record, new CorrectionReviewData(
                            $toUtc($data['clock_in_at'] ?? null),
                            $toUtc($data['clock_out_at'] ?? null),
                            $data['explanation'] ?? null,
                        ));
                        Notification::make()->success()->title('Correction request approved')->send();
                    }),
                Action::make('reject')
                    ->color('danger')
                    ->visible(fn (AttendanceCorrectionRequest $record) => $record->status === CorrectionRequestStatus::Pending)
                    ->schema([Textarea::make('reason')->required()->minLength(10)])
                    ->requiresConfirmation()
                    ->action(function (AttendanceCorrectionRequest $record, array $data, CorrectionReviewService $reviews): void {
                        $reviews->reject(auth()->user(), $record, $data['reason']);
                        Notification::make()->success()->title('Correction request rejected')->send();
                    }),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return ['index' => ListCorrectionRequests::route('/')];
    }
}
