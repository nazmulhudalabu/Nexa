<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Post extends Model
{
    protected $fillable = [
        'name',
        'description',
        'image',
        'user_id',
        'category_id',
        'share_token',
        'visibility', 'location', 'feeling', 'link_url', 'link_title', 'link_description', 'link_thumbnail',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'category_id' => 'integer',
    ];

    protected $with = ['user', 'category', 'tags'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
    public function tags(): BelongsToMany { return $this->belongsToMany(Tag::class); }
    public function comments(): HasMany { return $this->hasMany(Comment::class)->latest(); }
    public function likes(): HasMany { return $this->hasMany(Like::class); }
    public function media(): HasMany { return $this->hasMany(PostMedia::class); }
    public function reactions(): HasMany { return $this->hasMany(Reaction::class); }
    public function shares(): HasMany { return $this->hasMany(Share::class); }
}
