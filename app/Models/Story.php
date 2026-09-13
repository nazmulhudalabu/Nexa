<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Story extends Model
{
    protected $fillable = ['user_id', 'seed_key', 'type', 'privacy', 'custom_user_ids', 'hidden_user_ids', 'text', 'image', 'media_url', 'music', 'poll_options', 'expires_at'];

    protected $casts = ['expires_at' => 'datetime', 'custom_user_ids' => 'array', 'hidden_user_ids' => 'array', 'poll_options' => 'array'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function viewers(): HasMany { return $this->hasMany(StoryViewer::class); }
    public function reactions(): HasMany { return $this->hasMany(StoryReaction::class); }
    public function replies(): HasMany { return $this->hasMany(StoryReply::class); }
}
