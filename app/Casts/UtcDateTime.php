<?php

namespace App\Casts;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class UtcDateTime implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?CarbonImmutable
    {
        if ($value === null) {
            return null;
        }

        return CarbonImmutable::createFromFormat('Y-m-d H:i:s', (string) $value, 'UTC');
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        $instant = $value instanceof DateTimeInterface
            ? CarbonImmutable::instance($value)->utc()
            : CarbonImmutable::createFromFormat('Y-m-d H:i:s', (string) $value, 'UTC');

        return $instant->format('Y-m-d H:i:s');
    }
}
