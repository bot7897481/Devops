<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'applicant_id',
        'exam_session_id',
        'started_at',
        'completed_at',
        'total_score',
        'section_1_score',
        'section_2_score',
        'section_3_score',
        'section_4_score',
        'section_5_score',
        'section_1_time',
        'section_2_time',
        'section_3_time',
        'section_4_time',
        'section_5_time',
        'flagged',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'total_score' => 'decimal:2',
        'section_1_score' => 'decimal:2',
        'section_2_score' => 'decimal:2',
        'section_3_score' => 'decimal:2',
        'section_4_score' => 'decimal:2',
        'section_5_score' => 'decimal:2',
        'flagged' => 'boolean',
    ];

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }

    public function examSession(): BelongsTo
    {
        return $this->belongsTo(ExamSession::class);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(ExamResponse::class);
    }

    public function calculateScore(): void
    {
        $responses = $this->responses()->get();
        
        // Calculate scores by section
        $sectionScores = [];
        for ($i = 1; $i <= 5; $i++) {
            $sectionResponses = $responses->filter(function($response) use ($i) {
                $section = $this->getSectionName($i);
                return $response->question->section === $section;
            });

            $correctCount = $sectionResponses->where('is_correct', true)->count();
            $totalCount = $sectionResponses->count();
            
            if ($totalCount > 0) {
                $sectionScores[$i] = ($correctCount / $totalCount) * 100;
            } else {
                $sectionScores[$i] = 0;
            }
        }

        // Calculate total score (average of all sections)
        $totalScore = array_sum($sectionScores) / count($sectionScores);

        $this->update([
            'section_1_score' => $sectionScores[1],
            'section_2_score' => $sectionScores[2],
            'section_3_score' => $sectionScores[3],
            'section_4_score' => $sectionScores[4],
            'section_5_score' => $sectionScores[5],
            'total_score' => $totalScore,
        ]);
    }

    public function isPassing(): bool
    {
        $passingScore = config('app.exam_passing_score', 70);
        return $this->total_score >= $passingScore;
    }

    private function getSectionName(int $sectionNumber): string
    {
        return match($sectionNumber) {
            1 => 'reading',
            2 => 'math_computation',
            3 => 'applied_math',
            4 => 'language',
            5 => 'aptitude',
            default => 'reading',
        };
    }
}
