<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamSession extends Model
{
    use HasFactory;

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

    public function assignments(): HasMany
    {
        return $this->hasMany(ExamAssignment::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }

    public function isFull(): bool
    {
        return $this->filled_count >= $this->capacity;
    }

    public function incrementFilledCount(): void
    {
        $this->increment('filled_count');
    }

    public function decrementFilledCount(): void
    {
        $this->decrement('filled_count');
    }
}
