<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserQuizScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'quiz_id',
        'score',
        'total_questions',
        'percentage',
        'submitted_at'
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'percentage' => 'decimal:2'
    ];

    /**
     * Get the user that owns the quiz score.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the quiz that the score belongs to.
     */
    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }
} 
