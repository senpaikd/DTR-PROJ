<?php

namespace App\Domain\Settings;

use App\Models\ApplicationSetting;
use DateTimeZone;
use Throwable;

class ApplicationSettings
{
    public const DEFAULT_TIMEZONE = 'Asia/Manila';

    public const DEFAULT_CORRECTION_WINDOW_DAYS = 30;

    public function timezone(): DateTimeZone
    {
        $name = $this->value('organization_timezone', config('app.timezone', self::DEFAULT_TIMEZONE));

        try {
            return new DateTimeZone(is_string($name) ? $name : self::DEFAULT_TIMEZONE);
        } catch (Throwable) {
            return new DateTimeZone(self::DEFAULT_TIMEZONE);
        }
    }

    public function correctionWindowDays(): int
    {
        $days = filter_var($this->value('correction_window_days', self::DEFAULT_CORRECTION_WINDOW_DAYS), FILTER_VALIDATE_INT);

        return is_int($days) && $days >= 1 && $days <= 365 ? $days : self::DEFAULT_CORRECTION_WINDOW_DAYS;
    }

    private function value(string $key, mixed $default): mixed
    {
        return ApplicationSetting::query()->where('key', $key)->value('value') ?? $default;
    }
}
