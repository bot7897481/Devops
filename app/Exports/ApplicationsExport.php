<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ApplicationsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $applications;

    public function __construct($applications)
    {
        $this->applications = $applications;
    }

    public function collection()
    {
        return $this->applications;
    }

    public function headings(): array
    {
        return [
            'Confirmation Number',
            'First Name',
            'Last Name',
            'Email',
            'Phone',
            'Date of Birth',
            'Address',
            'City',
            'State',
            'Zip',
            'High School',
            'Graduation Date',
            'Application Date',
            'Validation Date',
            'Status',
            'Is Validated',
        ];
    }

    public function map($applicant): array
    {
        return [
            $applicant->confirmation_number,
            $applicant->first_name,
            $applicant->last_name,
            $applicant->email,
            $applicant->phone_primary,
            $applicant->dob?->format('Y-m-d'),
            $applicant->address_street,
            $applicant->address_city,
            $applicant->address_state,
            $applicant->address_zip,
            $applicant->high_school_name,
            $applicant->hs_graduation_date?->format('Y-m-d'),
            $applicant->application_timestamp?->format('Y-m-d H:i:s'),
            $applicant->validation_timestamp?->format('Y-m-d H:i:s'),
            $applicant->status,
            $applicant->is_validated ? 'Yes' : 'No',
        ];
    }
}
