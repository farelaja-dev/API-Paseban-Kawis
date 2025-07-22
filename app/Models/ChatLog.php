<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'session_id',
        'prompt',
        'response',
        'created_at'
    ];

    public function session()
    {
        return $this->belongsTo(ChatSession::class, 'session_id');
    }
}
