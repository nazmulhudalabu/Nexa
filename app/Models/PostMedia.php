<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostMedia extends Model
{
    protected $fillable = ['post_id', 'path', 'type', 'original_name'];
    public function post(): BelongsTo { return $this->belongsTo(Post::class); }
    public function getUrlAttribute(): string { return asset('storage/'.$this->path); }
}