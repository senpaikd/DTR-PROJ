<?php

namespace App\Support;

class SpreadsheetTextSanitizer
{
    public static function literal(string $value): string
    {
        return preg_match('/^[=+\-@]/u', ltrim($value)) === 1 ? "'{$value}" : $value;
    }
}
