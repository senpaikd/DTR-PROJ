<?php

namespace Tests\Unit\Accounts;

use App\Domain\Accounts\TemporaryPassphraseGenerator;
use PHPUnit\Framework\TestCase;

class TemporaryPassphraseGeneratorTest extends TestCase
{
    public function test_it_generates_readable_strong_unique_passphrases(): void
    {
        $generator = new TemporaryPassphraseGenerator;
        $values = array_map(fn () => $generator->generate(), range(1, 20));

        $this->assertCount(20, array_unique($values));
        foreach ($values as $value) {
            $this->assertGreaterThanOrEqual(14, strlen($value));
            $this->assertMatchesRegularExpression('/[A-Z]/', $value);
            $this->assertMatchesRegularExpression('/[a-z]/', $value);
            $this->assertMatchesRegularExpression('/\d/', $value);
            $this->assertMatchesRegularExpression('/[^A-Za-z0-9]/', $value);
        }
    }
}
