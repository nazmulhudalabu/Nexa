<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoverPhoto extends Model
{
    protected $fillable = ['user_id', 'path', 'is_current'];
    protected $casts = ['is_current' => 'boolean'];
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}