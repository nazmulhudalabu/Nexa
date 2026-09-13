<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Follower extends Model
{
    public $timestamps = false;

    protected $fillable = ['follower_id', 'following_id'];

    protected $casts = [
        'follower_id' => 'integer',
        'following_id' => 'integer',
        'created_at' => 'datetime',
    ];

    public function follower(): BelongsTo { return $this->belongsTo(User::class, 'follower_id'); }
    public function following(): BelongsTo { return $this->belongsTo(User::class, 'following_id'); }
}
