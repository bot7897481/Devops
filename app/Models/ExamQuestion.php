<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'section',
        'question_text',
        'question_image_path',
        'option_a',
        'option_b',
        'option_c',
        'option_d',
        'correct_answer',
        'difficulty_level',
        'tags',
        'times_used',
    ];

    protected $casts = [
        'tags' => 'array',
    ];

    public function responses(): HasMany
    {
        return $this->hasMany(ExamResponse::class, 'question_id');
    }

    public function incrementUsage(): void
    {
        $this->increment('times_used');
    }

    public function checkAnswer(string $selectedAnswer): bool
    {
        return $this->correct_answer === $selectedAnswer;
    }

    public function getSectionTimeLimit(): int
    {
        // Returns time limit in minutes
        return match($this->section) {
            'reading' => 25,
            'math_computation' => 9,
            'applied_math' => 25,
            'language' => 18,
            'aptitude' => 20,
            default => 20,
        };
    }

    public function getSectionQuestionCount(): int
    {
        return match($this->section) {
            'reading' => 25,
            'math_computation' => 15,
            'applied_math' => 25,
            'language' => 25,
            'aptitude' => 36,
            default => 25,
        };
    }
}
