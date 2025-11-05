<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_attempt_id',
        'question_id',
        'selected_answer',
        'is_correct',
        'time_spent',
        'marked_for_review',
        'answered_at',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'marked_for_review' => 'boolean',
        'answered_at' => 'datetime',
    ];

    // Relationships
    public function examAttempt()
    {
        return $this->belongsTo(ExamAttempt::class);
    }

    public function question()
    {
        return $this->belongsTo(ExamQuestion::class);
    }
}
