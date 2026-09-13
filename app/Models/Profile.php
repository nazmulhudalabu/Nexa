<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Profile extends Model
{
    protected $fillable = ['user_id', 'username', 'gender', 'birthday', 'relationship_status', 'website', 'contact_email', 'contact_phone', 'visibility'];
    protected function casts(): array { return ['birthday' => 'date', 'visibility' => 'array']; }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}