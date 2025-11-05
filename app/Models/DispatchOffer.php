<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class DispatchOffer extends Model implements Auditable
{
    use HasFactory, \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'dispatch_request_id',
        'applicant_id',
        'offered_at',
        'response_deadline',
        'response_at',
        'response_status',
        'decline_reason',
    ];

    protected $casts = [
        'offered_at' => 'datetime',
        'response_deadline' => 'datetime',
        'response_at' => 'datetime',
    ];

    // Relationships
    public function dispatchRequest()
    {
        return $this->belongsTo(DispatchRequest::class);
    }

    public function applicant()
    {
        return $this->belongsTo(Applicant::class);
    }

    public function indenture()
    {
        return $this->hasOne(Indenture::class);
    }

    // Check if expired
    public function isExpired(): bool
    {
        return now()->isAfter($this->response_deadline) && $this->response_status === 'pending';
    }
}
