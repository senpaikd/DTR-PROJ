<?php

namespace App\Filament\Resources\AttendanceRecords;

use App\Domain\Corrections\CorrectionReviewService;
use App\Domain\Settings\ApplicationSettings;
use App\Enums\AttendanceStatus;
use App\Filament\Resources\AttendanceRecords\Pages\ListAttendanceRecords;
use App\Models\AttendanceRecord;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AttendanceRecordResource extends Resource
{
    protected static ?string $model = AttendanceRecord::class;

    protected static ?string $recordTitleAttribute = 'work_date';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('work_date')->date()->sortable(),
                TextColumn::make('employeeProfile.employee_number')->label('Employee no.')->searchable(),
                TextColumn::make('employeeProfile.user.name')->label('Employee')->searchable()->sortable(),
                TextColumn::make('employeeProfile.department.name')->label('Department')->sortable(),
                TextColumn::make('clock_in_at')->label('Clock in')->dateTime()->placeholder('—'),
                TextColumn::make('clock_out_at')->label('Clock out')->dateTime()->placeholder('—'),
                TextColumn::make('status')->badge()->formatStateUsing(fn (AttendanceStatus $state) => str($state->value)->replace('_', ' ')->title()),
                IconColumn::make('clock_in_outside_schedule')->boolean()->label('Outside schedule'),
            ])
            ->filters([
                SelectFilter::make('status')->options(collect(AttendanceStatus::cases())->mapWithKeys(fn ($case) => [$case->value => str($case->value)->replace('_', ' ')->title()])->all()),
                SelectFilter::make('department')->relationship('employeeProfile.department', 'name'),
            ])
            ->defaultSort('work_date', 'desc')
            ->recordActions([
                Action::make('correct')
                    ->label('Correct record')
                    ->schema([
                        DateTimePicker::make('clock_in_at')->label('Clock in')->seconds(false),
                        DateTimePicker::make('clock_out_at')->label('Clock out')->seconds(false)->after('clock_in_at'),
                        Textarea::make('reason')->required()->minLength(10)->columnSpanFull(),
                    ])
                    ->fillForm(fn (AttendanceRecord $record, ApplicationSettings $settings) => [
                        'clock_in_at' => $record->clock_in_at?->setTimezone($settings->timezone())->format('Y-m-d H:i:s'),
                        'clock_out_at' => $record->clock_out_at?->setTimezone($settings->timezone())->format('Y-m-d H:i:s'),
                    ])
                    ->action(function (AttendanceRecord $record, array $data, CorrectionReviewService $reviews, ApplicationSettings $settings): void {
                        $toUtc = fn (?string $value) => $value
                            ? CarbonImmutable::parse($value, $settings->timezone())->utc()->format('Y-m-d H:i:s')
                            : null;
                        $reviews->correctDirectly(auth()->user(), $record, [
                            'clock_in_at' => $toUtc($data['clock_in_at'] ?? null),
                            'clock_out_at' => $toUtc($data['clock_out_at'] ?? null),
                        ], $data['reason']);
                        Notification::make()->success()->title('Attendance record corrected')->send();
                    }),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return ['index' => ListAttendanceRecords::route('/')];
    }
}
