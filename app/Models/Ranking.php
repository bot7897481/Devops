<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Ranking extends Model implements Auditable
{
    use HasFactory, \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'applicant_id',
        'rank',
        'exam_score',
        'application_timestamp',
        'validation_timestamp',
        'is_internal_promotion',
        'employment_start_date',
        'ranked_at',
    ];

    protected $casts = [
        'application_timestamp' => 'datetime',
        'validation_timestamp' => 'datetime',
        'is_internal_promotion' => 'boolean',
        'employment_start_date' => 'date',
        'ranked_at' => 'datetime',
    ];

    // Relationships
    public function applicant()
    {
        return $this->belongsTo(Applicant::class);
    }
}
