<?php

namespace App\Filament\Resources\AttendanceRecords\Pages;

use App\Domain\Corrections\CorrectionReviewService;
use App\Domain\Settings\ApplicationSettings;
use App\Filament\Resources\AttendanceRecords\AttendanceRecordResource;
use App\Models\EmployeeProfile;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListAttendanceRecords extends ListRecords
{
    protected static string $resource = AttendanceRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createAttendance')
                ->label('Add attendance')
                ->schema([
                    Select::make('employee_profile_id')
                        ->label('Employee')
                        ->options(fn () => EmployeeProfile::query()
                            ->with('user')
                            ->whereHas('user', fn ($query) => $query->where('is_active', true))
                            ->get()
                            ->sortBy(fn (EmployeeProfile $profile) => $profile->user->name)
                            ->mapWithKeys(fn (EmployeeProfile $profile) => [$profile->id => "{$profile->employee_number} — {$profile->user->name}"])
                            ->all())
                        ->searchable()
                        ->required(),
                    DatePicker::make('work_date')->required()->maxDate(today()),
                    DateTimePicker::make('clock_in_at')->label('Clock in')->seconds(false),
                    DateTimePicker::make('clock_out_at')->label('Clock out')->seconds(false)->after('clock_in_at'),
                    Textarea::make('reason')->required()->minLength(10)->columnSpanFull(),
                ])
                ->action(function (array $data, CorrectionReviewService $reviews, ApplicationSettings $settings): void {
                    $toUtc = fn (?string $value) => $value
                        ? CarbonImmutable::parse($value, $settings->timezone())->utc()->format('Y-m-d H:i:s')
                        : null;

                    $reviews->createDirectly(
                        auth()->user(),
                        EmployeeProfile::query()->findOrFail($data['employee_profile_id']),
                        $data['work_date'],
                        [
                            'clock_in_at' => $toUtc($data['clock_in_at'] ?? null),
                            'clock_out_at' => $toUtc($data['clock_out_at'] ?? null),
                        ],
                        $data['reason'],
                    );

                    Notification::make()->success()->title('Attendance record created')->send();
                }),
        ];
    }
}
