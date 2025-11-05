<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RankingsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $rankings;

    public function __construct($rankings)
    {
        $this->rankings = $rankings;
    }

    public function collection()
    {
        return $this->rankings;
    }

    public function headings(): array
    {
        return [
            'Rank',
            'Confirmation Number',
            'First Name',
            'Last Name',
            'Email',
            'Phone',
            'Total Score',
            'Section 1',
            'Section 2',
            'Section 3',
            'Section 4',
            'Section 5',
            'Application Date',
            'Validation Date',
            'Exam Completed',
            'Ranked At',
        ];
    }

    public function map($ranking): array
    {
        $applicant = $ranking->applicant;
        $exam = $applicant->examAttempt;

        return [
            $ranking->rank,
            $applicant->confirmation_number,
            $applicant->first_name,
            $applicant->last_name,
            $applicant->email,
            $applicant->phone_primary,
            $exam ? $exam->total_score : 'N/A',
            $exam ? $exam->section_1_score : 'N/A',
            $exam ? $exam->section_2_score : 'N/A',
            $exam ? $exam->section_3_score : 'N/A',
            $exam ? $exam->section_4_score : 'N/A',
            $exam ? $exam->section_5_score : 'N/A',
            $ranking->application_timestamp ? $ranking->application_timestamp->format('Y-m-d H:i:s') : 'N/A',
            $ranking->validation_timestamp ? $ranking->validation_timestamp->format('Y-m-d H:i:s') : 'N/A',
            $exam && $exam->completed_at ? $exam->completed_at->format('Y-m-d H:i:s') : 'N/A',
            $ranking->ranked_at ? $ranking->ranked_at->format('Y-m-d H:i:s') : 'N/A',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
