<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialConnection extends Model
{
    protected $fillable = ['user_id', 'target_user_id', 'type', 'status'];
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function target(): BelongsTo { return $this->belongsTo(User::class, 'target_user_id'); }
}