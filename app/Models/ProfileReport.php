<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfileReport extends Model
{
    protected $fillable = ['user_id', 'reported_user_id', 'reason', 'details', 'status'];
}