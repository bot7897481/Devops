<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class ExamQuestion extends Model implements Auditable
{
    use HasFactory, \OwenIt\Auditing\Auditable;

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

    // Relationships
    public function responses()
    {
        return $this->hasMany(ExamResponse::class, 'question_id');
    }

    // Check if answer is correct
    public function isCorrectAnswer(string $answer): bool
    {
        return strtolower($answer) === strtolower($this->correct_answer);
    }

    // Increment usage count
    public function incrementUsage(): void
    {
        $this->increment('times_used');
    }

    // Get random questions for a section
    public static function getRandomQuestionsForSection(string $section, int $count): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('section', $section)
            ->inRandomOrder()
            ->limit($count)
            ->get();
    }
}
