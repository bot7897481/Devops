<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamResponse extends Model
{
    use HasFactory;

    public $timestamps = false;

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

    public function examAttempt(): BelongsTo
    {
        return $this->belongsTo(ExamAttempt::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(ExamQuestion::class, 'question_id');
    }

    public function recordAnswer(string $answer, int $timeSpent): void
    {
        $this->update([
            'selected_answer' => $answer,
            'is_correct' => $this->question->checkAnswer($answer),
            'time_spent' => $timeSpent,
            'answered_at' => now(),
        ]);
    }
}
