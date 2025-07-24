<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ChatLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'prompt',
        'response',
    ];

    public function chatSession()
    {
        return $this->belongsTo(ChatSession::class, 'session_id');
    }
}
