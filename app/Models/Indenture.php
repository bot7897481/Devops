<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Indenture extends Model implements Auditable
{
    use HasFactory, \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'dispatch_offer_id',
        'applicant_id',
        'company_name',
        'start_date',
        'term_length_years',
        'wage_schedule',
        'document_template_path',
        'signed_document_path',
        'apprentice_signed_at',
        'employer_signed_at',
        'union_signed_at',
        'status',
        'entered_in_unionnet',
        'unionnet_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'wage_schedule' => 'array',
        'apprentice_signed_at' => 'datetime',
        'employer_signed_at' => 'datetime',
        'union_signed_at' => 'datetime',
        'entered_in_unionnet' => 'boolean',
    ];

    // Relationships
    public function dispatchOffer()
    {
        return $this->belongsTo(DispatchOffer::class);
    }

    public function applicant()
    {
        return $this->belongsTo(Applicant::class);
    }

    // Check if fully signed
    public function isFullySigned(): bool
    {
        return $this->apprentice_signed_at &&
               $this->employer_signed_at &&
               $this->union_signed_at;
    }
}
