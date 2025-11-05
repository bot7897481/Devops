<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class ExamAttempt extends Model implements Auditable
{
    use HasFactory, \OwenIt\Auditing\Auditable;

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
        'flagged' => 'boolean',
    ];

    // Relationships
    public function applicant()
    {
        return $this->belongsTo(Applicant::class);
    }

    public function examSession()
    {
        return $this->belongsTo(ExamSession::class);
    }

    public function responses()
    {
        return $this->hasMany(ExamResponse::class);
    }

    // Calculate total score from all sections
    public function calculateTotalScore(): float
    {
        $sectionScores = [
            $this->section_1_score ?? 0,
            $this->section_2_score ?? 0,
            $this->section_3_score ?? 0,
            $this->section_4_score ?? 0,
            $this->section_5_score ?? 0,
        ];

        return round(array_sum($sectionScores) / 5, 2);
    }

    // Check if passed
    public function hasPassed(float $passingScore = 70.0): bool
    {
        return $this->total_score >= $passingScore;
    }
}
