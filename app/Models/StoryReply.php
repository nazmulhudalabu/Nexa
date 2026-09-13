<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class StoryReply extends Model { protected $fillable = ['story_id', 'user_id', 'body']; }
