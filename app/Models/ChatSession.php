<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatSession extends Model
{
    protected $fillable = ['user_id', 'started_at', 'ended_at'];

    public function logs()
    {
        return $this->hasMany(ChatLog::class, 'session_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
