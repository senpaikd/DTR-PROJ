<?php

namespace App\Filament\Widgets;

use App\Domain\Settings\ApplicationSettings;
use App\Enums\AttendanceStatus;
use App\Enums\CorrectionRequestStatus;
use App\Enums\UserRole;
use App\Models\AttendanceCorrectionRequest;
use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AttendanceOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $timezone = app(ApplicationSettings::class)->timezone();
        $today = CarbonImmutable::now($timezone)->toDateString();

        return [
            Stat::make('Active employees', User::query()->where('role', UserRole::Employee->value)->where('is_active', true)->count()),
            Stat::make('Recorded today', AttendanceRecord::query()->whereDate('work_date', $today)->count()),
            Stat::make('Currently clocked in', AttendanceRecord::query()->where('status', AttendanceStatus::ClockedIn->value)->count()),
            Stat::make('Pending corrections', AttendanceCorrectionRequest::query()->where('status', CorrectionRequestStatus::Pending->value)->count()),
        ];
    }
}
