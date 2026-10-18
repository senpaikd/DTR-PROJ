<?php

namespace App\Models;

use App\Casts\UtcDateTime;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['actor_id', 'action', 'subject_type', 'subject_id', 'before_values', 'after_values', 'metadata', 'ip_address', 'user_agent', 'created_at'];

    protected function casts(): array
    {
        return ['before_values' => 'array', 'after_values' => 'array', 'metadata' => 'array', 'created_at' => UtcDateTime::class];
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function subject()
    {
        return $this->morphTo();
    }
}
