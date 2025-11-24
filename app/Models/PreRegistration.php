<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class PreRegistration extends Model implements Auditable
{
    use HasFactory, \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'document_path',
        'reference_number',
        'notified_at',
    ];

    protected $casts = [
        'notified_at' => 'datetime',
    ];

    // Generate unique reference number
    public static function generateReferenceNumber(): string
    {
        return 'PRE-' . date('Y') . '-' . strtoupper(substr(uniqid(), -6));
    }
}
