<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    protected $fillable = ['user_one_id', 'user_two_id', 'type', 'name', 'picture', 'created_by', 'is_muted'];
    protected $casts = ['user_one_id' => 'integer', 'user_two_id' => 'integer'];
    public function userOne(): BelongsTo { return $this->belongsTo(User::class, 'user_one_id'); }
    public function userTwo(): BelongsTo { return $this->belongsTo(User::class, 'user_two_id'); }
    public function messages(): HasMany { return $this->hasMany(Message::class)->with('user')->orderBy('id'); }
    public function members(): HasMany { return $this->hasMany(ConversationMember::class)->with('user'); }
    public function otherUser(int $userId): User
    {
        return (int) $this->user_one_id === $userId
            ? $this->userTwo
            : $this->userOne;
    }

    public function isGroup(): bool { return $this->type === 'GROUP'; }
}