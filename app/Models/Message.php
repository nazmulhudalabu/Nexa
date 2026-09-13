<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable = [
        'conversation_id',
        'user_id',
        'body',
        'attachment_path',
        'attachment_name',
        'attachment_mime',
        'attachment_size',
        'read_at',
        'type', 'status', 'reply_to_id', 'edited_at', 'deleted_at', 'pinned',
    ];

    protected $casts = [
        'conversation_id' => 'integer',
        'user_id' => 'integer',
        'attachment_size' => 'integer',
        'read_at' => 'datetime',
        'edited_at' => 'datetime', 'deleted_at' => 'datetime', 'pinned' => 'boolean',
    ];

    public function conversation(): BelongsTo { return $this->belongsTo(Conversation::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function replyTo(): BelongsTo { return $this->belongsTo(Message::class, 'reply_to_id'); }

    public function getAttachmentUrlAttribute(): string
    {
        return asset('storage/'.$this->attachment_path);
    }
}
