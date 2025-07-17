<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailOtp extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'kode_otp', 'type', 'expired_at'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
} 
