<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class Applicant extends Model implements Auditable
{
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'user_id',
        'confirmation_number',
        'first_name',
        'middle_name',
        'last_name',
        'dob',
        'ssn_last4',
        'address_street',
        'address_city',
        'address_state',
        'address_zip',
        'mailing_address',
        'phone_primary',
        'phone_alternate',
        'email',
        'high_school_name',
        'hs_graduation_date',
        'diploma_path',
        'id_document_front_path',
        'id_document_back_path',
        'work_experience',
        'application_timestamp',
        'validation_timestamp',
        'is_validated',
        'validation_admin_id',
        'photo_biometric_path',
        'fingerprint_hash',
        'status',
    ];

    protected $casts = [
        'dob' => 'date',
        'hs_graduation_date' => 'date',
        'mailing_address' => 'array',
        'work_experience' => 'array',
        'application_timestamp' => 'datetime',
        'validation_timestamp' => 'datetime',
        'is_validated' => 'boolean',
    ];

    protected $hidden = [
        'ssn_last4',
        'fingerprint_hash',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function validationAdmin()
    {
        return $this->belongsTo(User::class, 'validation_admin_id');
    }

    public function examAssignment()
    {
        return $this->hasOne(ExamAssignment::class);
    }

    public function examAttempt()
    {
        return $this->hasOne(ExamAttempt::class);
    }

    public function ranking()
    {
        return $this->hasOne(Ranking::class);
    }

    public function dispatchOffers()
    {
        return $this->hasMany(DispatchOffer::class);
    }

    public function indentures()
    {
        return $this->hasMany(Indenture::class);
    }

    // Generate unique confirmation number
    public static function generateConfirmationNumber(): string
    {
        return 'L39-' . date('Y') . '-' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
    }

    // Full name accessor
    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->middle_name . ' ' . $this->last_name);
    }
}
