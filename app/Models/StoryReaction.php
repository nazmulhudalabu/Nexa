<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class StoryReaction extends Model { protected $fillable = ['story_id', 'user_id', 'reaction_type']; }
