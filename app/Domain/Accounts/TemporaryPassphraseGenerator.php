<?php

namespace App\Domain\Accounts;

class TemporaryPassphraseGenerator
{
    private const WORDS = ['Amber', 'Bamboo', 'Cedar', 'Dawn', 'Harbor', 'Jade', 'Mango', 'River', 'Sampaguita', 'Solstice', 'Tamarind', 'Willow'];

    public function generate(): string
    {
        $first = self::WORDS[random_int(0, count(self::WORDS) - 1)];
        $second = strtolower(self::WORDS[random_int(0, count(self::WORDS) - 1)]);

        return $first.'-'.$second.'-'.random_int(1000, 9999).'!';
    }
}
