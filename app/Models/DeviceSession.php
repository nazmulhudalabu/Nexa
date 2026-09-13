<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceSession extends Model
{
    protected $fillable = ['user_id', 'session_id', 'name', 'ip_address', 'user_agent', 'last_active_at'];

    protected function casts(): array
    {
        return ['last_active_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}