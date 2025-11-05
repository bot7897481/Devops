<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'applicant_id',
        'exam_session_id',
        'checked_in_at',
        'check_in_verified_by',
        'exam_started_at',
        'exam_completed_at',
        'status',
    ];

    protected $casts = [
        'checked_in_at' => 'datetime',
        'exam_started_at' => 'datetime',
        'exam_completed_at' => 'datetime',
    ];

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }

    public function examSession(): BelongsTo
    {
        return $this->belongsTo(ExamSession::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'check_in_verified_by');
    }

    public function checkIn(int $adminId): void
    {
        $this->update([
            'checked_in_at' => now(),
            'check_in_verified_by' => $adminId,
            'status' => 'checked_in',
        ]);
    }

    public function startExam(): void
    {
        $this->update([
            'exam_started_at' => now(),
            'status' => 'in_progress',
        ]);
    }

    public function completeExam(): void
    {
        $this->update([
            'exam_completed_at' => now(),
            'status' => 'completed',
        ]);
    }
}
