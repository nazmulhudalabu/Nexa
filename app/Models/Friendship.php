<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Friendship extends Model
{
    public const NONE = 'NONE';
    public const REQUEST_SENT = 'REQUEST_SENT';
    public const REQUEST_RECEIVED = 'REQUEST_RECEIVED';
    public const FRIENDS = 'FRIENDS';
    public const BLOCKED = 'BLOCKED';

    protected $fillable = ['user_one_id', 'user_two_id', 'sender_id', 'status'];

    public function userOne(): BelongsTo { return $this->belongsTo(User::class, 'user_one_id'); }
    public function userTwo(): BelongsTo { return $this->belongsTo(User::class, 'user_two_id'); }
    public function sender(): BelongsTo { return $this->belongsTo(User::class, 'sender_id'); }

    public function otherUser(int $userId): BelongsTo
    {
        return $this->user_one_id === $userId ? $this->userTwo() : $this->userOne();
    }

    public static function pair(int $firstUserId, int $secondUserId): array
    {
        return $firstUserId < $secondUserId
            ? [$firstUserId, $secondUserId]
            : [$secondUserId, $firstUserId];
    }
}
