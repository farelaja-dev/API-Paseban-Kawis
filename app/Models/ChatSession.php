<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ChatSession extends Model
{
 use HasFactory;

    protected $fillable = [
        'user_id',
        'ended_at',
    ];

    protected $casts = [
        'ended_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function chatLogs()
    {
        return $this->hasMany(ChatLog::class, 'session_id');
    }

    public function latestMessage()
    {
        return $this->hasOne(ChatLog::class, 'session_id')->latest();
    }
}
