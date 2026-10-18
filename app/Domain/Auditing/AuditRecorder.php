<?php

namespace App\Domain\Auditing;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AuditRecorder
{
    private const SENSITIVE_FRAGMENTS = ['password', 'token', 'secret', 'session', 'authorization', 'cookie'];

    public function __construct(private readonly Request $request) {}

    public function record(User $actor, string $action, ?Model $subject, array $before, array $after, array $metadata = []): AuditLog
    {
        return AuditLog::create([
            'actor_id' => $actor->id,
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'before_values' => $this->redact($before),
            'after_values' => $this->redact($after),
            'metadata' => $this->redact($metadata),
            'ip_address' => $this->request->ip(),
            'user_agent' => Str::limit((string) $this->request->userAgent(), 1000, ''),
            'created_at' => now(),
        ]);
    }

    private function redact(array $values): array
    {
        foreach ($values as $key => $value) {
            $sensitive = collect(self::SENSITIVE_FRAGMENTS)->contains(
                fn (string $fragment): bool => str_contains(mb_strtolower((string) $key), $fragment)
            );
            $values[$key] = $sensitive ? '[REDACTED]' : (is_array($value) ? $this->redact($value) : $value);
        }

        return $values;
    }
}
