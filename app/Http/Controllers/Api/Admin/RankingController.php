<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\Ranking;
use App\Models\ExamAttempt;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\RankingsExport;

class RankingController extends Controller
{
    /**
     * Generate rankings from completed exam attempts
     */
    public function generate(Request $request): JsonResponse
    {
        try {
            // Get all applicants with completed exams
            $applicants = Applicant::query()
                ->where('is_validated', true)
                ->whereHas('examAttempt', function ($query) {
                    $query->whereNotNull('completed_at')
                        ->whereNotNull('total_score');
                })
                ->with(['examAttempt', 'ranking'])
                ->get();

            if ($applicants->isEmpty()) {
                return response()->json([
                    'message' => 'No completed exam attempts found to rank',
                ], 400);
            }

            // Get passing score from config
            $passingScore = config('app.exam_passing_score', 70);

            // Filter only passing applicants
            $passingApplicants = $applicants->filter(function ($applicant) use ($passingScore) {
                return $applicant->examAttempt &&
                       $applicant->examAttempt->total_score >= $passingScore;
            });

            if ($passingApplicants->isEmpty()) {
                return response()->json([
                    'message' => 'No applicants passed the exam',
                ], 400);
            }

            // Sort by exam score (descending), then by application timestamp (ascending for tie-breaking)
            $sortedApplicants = $passingApplicants->sortBy([
                ['examAttempt.total_score', 'desc'],
                ['application_timestamp', 'asc'],
            ])->values();

            // Generate or update rankings
            $rankedCount = 0;
            foreach ($sortedApplicants as $index => $applicant) {
                Ranking::updateOrCreate(
                    ['applicant_id' => $applicant->id],
                    [
                        'rank' => $index + 1,
                        'exam_score' => $applicant->examAttempt->total_score,
                        'application_timestamp' => $applicant->application_timestamp,
                        'validation_timestamp' => $applicant->validation_timestamp,
                        'is_internal_promotion' => false, // Could be enhanced later
                        'ranked_at' => now(),
                    ]
                );
                $rankedCount++;
            }

            return response()->json([
                'message' => "Successfully generated rankings for {$rankedCount} applicants",
                'total_applicants' => $applicants->count(),
                'passing_applicants' => $passingApplicants->count(),
                'ranked_count' => $rankedCount,
                'passing_score' => $passingScore,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error generating rankings: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to generate rankings',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all rankings with filters and pagination
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Ranking::with(['applicant.user', 'applicant.examAttempt'])
                ->orderBy('rank', 'asc');

            // Filter by rank range
            if ($request->has('rank_from')) {
                $query->where('rank', '>=', $request->rank_from);
            }
            if ($request->has('rank_to')) {
                $query->where('rank', '<=', $request->rank_to);
            }

            // Filter by minimum score
            if ($request->has('min_score')) {
                $query->where('exam_score', '>=', $request->min_score);
            }

            // Search by name
            if ($request->has('search')) {
                $search = $request->search;
                $query->whereHas('applicant', function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('confirmation_number', 'like', "%{$search}%");
                });
            }

            // Pagination
            $perPage = $request->get('per_page', 50);
            $rankings = $query->paginate($perPage);

            // Format the response
            $rankings->getCollection()->transform(function ($ranking) {
                return [
                    'id' => $ranking->id,
                    'rank' => $ranking->rank,
                    'exam_score' => $ranking->exam_score,
                    'application_timestamp' => $ranking->application_timestamp,
                    'validation_timestamp' => $ranking->validation_timestamp,
                    'ranked_at' => $ranking->ranked_at,
                    'applicant' => [
                        'id' => $ranking->applicant->id,
                        'confirmation_number' => $ranking->applicant->confirmation_number,
                        'first_name' => $ranking->applicant->first_name,
                        'last_name' => $ranking->applicant->last_name,
                        'email' => $ranking->applicant->email,
                        'phone_primary' => $ranking->applicant->phone_primary,
                    ],
                    'exam_details' => $ranking->applicant->examAttempt ? [
                        'total_score' => $ranking->applicant->examAttempt->total_score,
                        'section_1_score' => $ranking->applicant->examAttempt->section_1_score,
                        'section_2_score' => $ranking->applicant->examAttempt->section_2_score,
                        'section_3_score' => $ranking->applicant->examAttempt->section_3_score,
                        'section_4_score' => $ranking->applicant->examAttempt->section_4_score,
                        'section_5_score' => $ranking->applicant->examAttempt->section_5_score,
                        'completed_at' => $ranking->applicant->examAttempt->completed_at,
                    ] : null,
                ];
            });

            return response()->json($rankings);
        } catch (\Exception $e) {
            \Log::error('Error fetching rankings: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to fetch rankings',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export rankings to Excel
     */
    public function export(Request $request)
    {
        try {
            $rankings = Ranking::with(['applicant.user', 'applicant.examAttempt'])
                ->orderBy('rank', 'asc')
                ->get();

            if ($rankings->isEmpty()) {
                return response()->json([
                    'message' => 'No rankings available to export',
                ], 400);
            }

            $fileName = 'rankings_' . date('Y-m-d_His') . '.xlsx';

            return Excel::download(new RankingsExport($rankings), $fileName);
        } catch (\Exception $e) {
            \Log::error('Error exporting rankings: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to export rankings',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get ranking statistics
     */
    public function statistics(): JsonResponse
    {
        try {
            $totalRanked = Ranking::count();
            $averageScore = Ranking::avg('exam_score');

            $topScores = Ranking::with('applicant')
                ->orderBy('rank', 'asc')
                ->limit(10)
                ->get();

            $scoreDistribution = [
                '90-100' => Ranking::whereBetween('exam_score', [90, 100])->count(),
                '80-89' => Ranking::whereBetween('exam_score', [80, 89.99])->count(),
                '70-79' => Ranking::whereBetween('exam_score', [70, 79.99])->count(),
            ];

            return response()->json([
                'total_ranked' => $totalRanked,
                'average_score' => round($averageScore, 2),
                'top_10' => $topScores,
                'score_distribution' => $scoreDistribution,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching ranking statistics: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to fetch statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
