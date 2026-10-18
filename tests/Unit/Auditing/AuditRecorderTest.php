<?php

namespace Tests\Unit\Auditing;

use App\Domain\Auditing\AuditRecorder;
use App\Models\EmployeeProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditRecorderTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_records_actor_subject_and_redacted_changes(): void
    {
        $actor = User::factory()->administrator()->create();
        $subject = EmployeeProfile::factory()->create();

        $log = app(AuditRecorder::class)->record(
            $actor,
            'employee.updated',
            $subject,
            ['name' => 'Before', 'password' => 'secret', 'nested' => ['remember_token' => 'token']],
            ['name' => 'After', 'api_token' => 'token'],
            ['reason' => 'Approved change', 'session_id' => 'secret'],
        );

        $this->assertTrue($log->actor->is($actor));
        $this->assertTrue($log->subject->is($subject));
        $this->assertSame('[REDACTED]', $log->before_values['password']);
        $this->assertSame('[REDACTED]', $log->before_values['nested']['remember_token']);
        $this->assertSame('[REDACTED]', $log->after_values['api_token']);
        $this->assertSame('[REDACTED]', $log->metadata['session_id']);
        $this->assertSame('After', $log->after_values['name']);
    }
}
