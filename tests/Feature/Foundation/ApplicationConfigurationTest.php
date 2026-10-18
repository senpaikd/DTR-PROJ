<?php

namespace Tests\Feature\Foundation;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ApplicationConfigurationTest extends TestCase
{
    public function test_the_application_uses_the_philippines_timezone_by_default(): void
    {
        $this->assertSame('Asia/Manila', config('app.timezone'));
    }

    public function test_public_account_creation_and_recovery_routes_are_not_available(): void
    {
        $this->assertFalse(Route::has('register'));
        $this->assertFalse(Route::has('password.request'));
    }

    public function test_the_administrator_login_page_is_available(): void
    {
        $this->get('/admin/login')->assertOk();
    }
}
