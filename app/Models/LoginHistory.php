<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginHistory extends Model
{
    protected $fillable = ['user_id', 'email', 'ip_address', 'user_agent', 'successful', 'failure_reason'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}