<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class ExamSession extends Model implements Auditable
{
    use HasFactory, \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'date',
        'time_slot',
        'start_time',
        'end_time',
        'location',
        'capacity',
        'filled_count',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    // Relationships
    public function assignments()
    {
        return $this->hasMany(ExamAssignment::class);
    }

    public function attempts()
    {
        return $this->hasMany(ExamAttempt::class);
    }

    // Check if session has available spots
    public function hasAvailableSpots(): bool
    {
        return $this->filled_count < $this->capacity;
    }

    // Increment filled count
    public function incrementFilledCount(): void
    {
        $this->increment('filled_count');
    }
}
