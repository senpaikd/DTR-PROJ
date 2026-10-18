<?php

namespace App\Domain\Accounts;

use App\Models\User;

final readonly class TemporaryCredential
{
    public function __construct(public User $user, public string $temporaryPassword) {}
}
