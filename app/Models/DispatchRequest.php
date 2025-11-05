<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class DispatchRequest extends Model implements Auditable
{
    use HasFactory, \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'chief_user_id',
        'company_name',
        'job_location_address',
        'job_location_city',
        'job_location_state',
        'job_type',
        'start_date',
        'positions_needed',
        'job_description',
        'special_requirements',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
    ];

    // Relationships
    public function chief()
    {
        return $this->belongsTo(User::class, 'chief_user_id');
    }

    public function dispatchOffers()
    {
        return $this->hasMany(DispatchOffer::class);
    }
}
