<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdministrator extends Command
{
    protected $signature = 'dtr:create-admin {--name=} {--email=} {--password=}';

    protected $description = 'Create an administrator account with a temporary password';

    public function handle(): int
    {
        $name = trim((string) ($this->option('name') ?: $this->ask('Name')));
        $email = mb_strtolower(trim((string) ($this->option('email') ?: $this->ask('Email'))));
        $password = (string) ($this->option('password') ?: $this->secret('Temporary password'));
        $validator = Validator::make(compact('name', 'email', 'password'), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::min(12)->mixedCase()->numbers()->symbols()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => UserRole::Administrator,
            'is_active' => true,
            'must_change_password' => true,
        ]);

        $this->info("Administrator {$email} created. Deliver the temporary password securely in person.");

        return self::SUCCESS;
    }
}
