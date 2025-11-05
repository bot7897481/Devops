<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class ExamAssignment extends Model implements Auditable
{
    use HasFactory, \OwenIt\Auditing\Auditable;

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

    // Relationships
    public function applicant()
    {
        return $this->belongsTo(Applicant::class);
    }

    public function examSession()
    {
        return $this->belongsTo(ExamSession::class);
    }

    public function verifiedByAdmin()
    {
        return $this->belongsTo(User::class, 'check_in_verified_by');
    }
}
