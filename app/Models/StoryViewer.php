<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class StoryViewer extends Model { public $timestamps = false; protected $fillable = ['story_id', 'user_id', 'viewed_at']; protected $casts = ['viewed_at' => 'datetime']; }
