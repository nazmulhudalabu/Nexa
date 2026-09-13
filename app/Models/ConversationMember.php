<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ConversationMember extends Model { protected $fillable = ['conversation_id','user_id','is_admin','last_read_at']; protected $casts = ['is_admin'=>'boolean','last_read_at'=>'datetime']; public function user(): BelongsTo { return $this->belongsTo(User::class); } public function conversation(): BelongsTo { return $this->belongsTo(Conversation::class); } }
