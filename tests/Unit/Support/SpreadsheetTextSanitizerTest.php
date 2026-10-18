<?php

namespace Tests\Unit\Support;

use App\Support\SpreadsheetTextSanitizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SpreadsheetTextSanitizerTest extends TestCase
{
    #[DataProvider('dangerousValues')]
    public function test_formula_like_values_are_exported_as_literal_text(string $value): void
    {
        $this->assertSame("'{$value}", SpreadsheetTextSanitizer::literal($value));
    }

    public static function dangerousValues(): array
    {
        return [['=SUM(A1:A2)'], ['+cmd'], ['-2+3'], ['@IMPORTXML(A1)']];
    }

    public function test_ordinary_text_is_unchanged(): void
    {
        $this->assertSame('Maria Santos', SpreadsheetTextSanitizer::literal('Maria Santos'));
    }
}
