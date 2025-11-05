<?php

namespace App\Http\Controllers\Api\Applicant;

use App\Http\Controllers\Controller;
use App\Models\Ranking;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RankingController extends Controller
{
    /**
     * Get the authenticated applicant's ranking
     */
    public function getMyRanking(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Get applicant record
            $applicant = $user->applicant;

            if (!$applicant) {
                return response()->json([
                    'message' => 'No application found',
                ], 404);
            }

            // Get ranking
            $ranking = Ranking::where('applicant_id', $applicant->id)
                ->with(['applicant.examAttempt'])
                ->first();

            if (!$ranking) {
                // Check if exam is completed
                $examAttempt = $applicant->examAttempt;

                if (!$examAttempt || !$examAttempt->completed_at) {
                    return response()->json([
                        'message' => 'Exam not completed yet',
                        'status' => 'exam_not_completed',
                    ], 404);
                }

                // Check if exam was passed
                $passingScore = config('app.exam_passing_score', 70);
                if ($examAttempt->total_score < $passingScore) {
                    return response()->json([
                        'message' => 'Exam score did not meet the passing requirement',
                        'status' => 'exam_failed',
                        'score' => $examAttempt->total_score,
                        'passing_score' => $passingScore,
                    ], 404);
                }

                return response()->json([
                    'message' => 'Rankings have not been generated yet. Please check back later.',
                    'status' => 'not_ranked_yet',
                ], 404);
            }

            // Get total number of ranked applicants
            $totalRanked = Ranking::count();

            // Calculate percentile
            $percentile = 100 - (($ranking->rank - 1) / $totalRanked * 100);

            return response()->json([
                'ranking' => [
                    'rank' => $ranking->rank,
                    'total_ranked' => $totalRanked,
                    'percentile' => round($percentile, 2),
                    'exam_score' => $ranking->exam_score,
                    'application_timestamp' => $ranking->application_timestamp,
                    'ranked_at' => $ranking->ranked_at,
                ],
                'exam_details' => [
                    'total_score' => $applicant->examAttempt->total_score,
                    'section_1_score' => $applicant->examAttempt->section_1_score,
                    'section_2_score' => $applicant->examAttempt->section_2_score,
                    'section_3_score' => $applicant->examAttempt->section_3_score,
                    'section_4_score' => $applicant->examAttempt->section_4_score,
                    'section_5_score' => $applicant->examAttempt->section_5_score,
                    'completed_at' => $applicant->examAttempt->completed_at,
                ],
                'message' => "You are ranked #{$ranking->rank} out of {$totalRanked} applicants",
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching applicant ranking: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to fetch ranking',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
