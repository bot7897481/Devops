<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\PreRegistration;
use App\Models\Applicant;
use App\Models\ExamAttempt;
use App\Models\ExamSession;
use App\Models\Ranking;
use App\Models\DispatchRequest;
use App\Models\DispatchOffer;
use App\Models\Indenture;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /**
     * Get comprehensive dashboard statistics
     */
    public function getDashboard(Request $request): JsonResponse
    {
        try {
            $dashboard = [
                'pre_registrations' => $this->getPreRegistrationStats(),
                'applications' => $this->getApplicationStats(),
                'exams' => $this->getExamStats(),
                'dispatch' => $this->getDispatchStats(),
                'recent_activity' => $this->getRecentActivity(),
            ];

            return response()->json($dashboard);
        } catch (\Exception $e) {
            \Log::error('Error fetching dashboard: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to fetch dashboard data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get pre-registration statistics
     */
    public function getPreRegistrationStats(): array
    {
        $total = PreRegistration::count();
        $today = PreRegistration::whereDate('created_at', today())->count();
        $thisWeek = PreRegistration::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count();
        $thisMonth = PreRegistration::whereMonth('created_at', now()->month)->count();

        // Get registrations by day for the last 30 days
        $dailyRegistrations = PreRegistration::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count')
        )
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'total' => $total,
            'today' => $today,
            'this_week' => $thisWeek,
            'this_month' => $thisMonth,
            'conversion_rate' => $total > 0 ? round((Applicant::count() / $total) * 100, 2) : 0,
            'daily_chart' => $dailyRegistrations,
        ];
    }

    /**
     * Get application statistics
     */
    public function getApplicationStats(): array
    {
        $total = Applicant::count();
        $validated = Applicant::where('is_validated', true)->count();
        $pending = Applicant::where('is_validated', false)->count();

        $statusBreakdown = Applicant::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();

        // Applications by day for the last 30 days
        $dailyApplications = Applicant::select(
            DB::raw('DATE(application_timestamp) as date'),
            DB::raw('COUNT(*) as count')
        )
            ->where('application_timestamp', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'total' => $total,
            'validated' => $validated,
            'pending_validation' => $pending,
            'validation_rate' => $total > 0 ? round(($validated / $total) * 100, 2) : 0,
            'by_status' => $statusBreakdown,
            'daily_chart' => $dailyApplications,
        ];
    }

    /**
     * Get exam statistics
     */
    public function getExamStats(): array
    {
        $totalSessions = ExamSession::count();
        $activeSessions = ExamSession::where('status', 'active')->count();
        $completedSessions = ExamSession::where('status', 'completed')->count();

        $totalAttempts = ExamAttempt::count();
        $completedAttempts = ExamAttempt::whereNotNull('completed_at')->count();
        $inProgress = ExamAttempt::whereNotNull('started_at')->whereNull('completed_at')->count();

        $averageScore = ExamAttempt::whereNotNull('total_score')->avg('total_score');
        $passingScore = config('app.exam_passing_score', 70);
        $passedCount = ExamAttempt::where('total_score', '>=', $passingScore)->count();
        $failedCount = ExamAttempt::where('total_score', '<', $passingScore)
            ->whereNotNull('total_score')
            ->count();

        // Score distribution
        $scoreDistribution = [
            '90-100' => ExamAttempt::whereBetween('total_score', [90, 100])->count(),
            '80-89' => ExamAttempt::whereBetween('total_score', [80, 89.99])->count(),
            '70-79' => ExamAttempt::whereBetween('total_score', [70, 79.99])->count(),
            '60-69' => ExamAttempt::whereBetween('total_score', [60, 69.99])->count(),
            '0-59' => ExamAttempt::whereBetween('total_score', [0, 59.99])->count(),
        ];

        return [
            'sessions' => [
                'total' => $totalSessions,
                'active' => $activeSessions,
                'completed' => $completedSessions,
            ],
            'attempts' => [
                'total' => $totalAttempts,
                'completed' => $completedAttempts,
                'in_progress' => $inProgress,
            ],
            'scores' => [
                'average' => round($averageScore, 2),
                'passing_score' => $passingScore,
                'passed' => $passedCount,
                'failed' => $failedCount,
                'pass_rate' => $completedAttempts > 0 ? round(($passedCount / $completedAttempts) * 100, 2) : 0,
            ],
            'score_distribution' => $scoreDistribution,
        ];
    }

    /**
     * Get dispatch statistics
     */
    public function getDispatchStats(): array
    {
        $totalRequests = DispatchRequest::count();
        $pendingRequests = DispatchRequest::where('status', 'pending')->count();
        $inProgressRequests = DispatchRequest::where('status', 'in_progress')->count();
        $completedRequests = DispatchRequest::where('status', 'completed')->count();

        $totalOffers = DispatchOffer::count();
        $pendingOffers = DispatchOffer::where('response_status', 'pending')->count();
        $acceptedOffers = DispatchOffer::where('response_status', 'accepted')->count();
        $declinedOffers = DispatchOffer::where('response_status', 'declined')->count();

        $totalPositions = DispatchRequest::sum('positions_needed');
        $filledPositions = DispatchOffer::where('response_status', 'accepted')->count();

        $rankedApplicants = Ranking::count();

        $totalIndentures = Indenture::count();
        $activeIndentures = Indenture::where('status', 'active')->count();
        $pendingSignatures = Indenture::where('status', '!=', 'active')
            ->whereNotNull('document_template_path')
            ->count();

        // Offers by response time (average hours to respond)
        $avgResponseTime = DispatchOffer::whereNotNull('response_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, offered_at, response_at)) as avg_hours')
            ->value('avg_hours');

        return [
            'requests' => [
                'total' => $totalRequests,
                'pending' => $pendingRequests,
                'in_progress' => $inProgressRequests,
                'completed' => $completedRequests,
            ],
            'offers' => [
                'total' => $totalOffers,
                'pending' => $pendingOffers,
                'accepted' => $acceptedOffers,
                'declined' => $declinedOffers,
                'acceptance_rate' => $totalOffers > 0 ? round(($acceptedOffers / $totalOffers) * 100, 2) : 0,
                'avg_response_hours' => round($avgResponseTime ?? 0, 2),
            ],
            'positions' => [
                'total_requested' => $totalPositions,
                'filled' => $filledPositions,
                'remaining' => max(0, $totalPositions - $filledPositions),
                'fill_rate' => $totalPositions > 0 ? round(($filledPositions / $totalPositions) * 100, 2) : 0,
            ],
            'rankings' => [
                'total_ranked' => $rankedApplicants,
                'available_for_dispatch' => Ranking::whereHas('applicant', function ($q) {
                    $q->whereIn('status', ['active', 'ranked']);
                })->count(),
            ],
            'indentures' => [
                'total' => $totalIndentures,
                'active' => $activeIndentures,
                'pending_signatures' => $pendingSignatures,
            ],
        ];
    }

    /**
     * Get recent activity across all modules
     */
    protected function getRecentActivity(): array
    {
        $activities = [];

        // Recent applications
        $recentApplications = Applicant::orderBy('application_timestamp', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($a) => [
                'type' => 'application',
                'message' => "New application from {$a->first_name} {$a->last_name}",
                'timestamp' => $a->application_timestamp,
            ]);

        // Recent exam completions
        $recentExams = ExamAttempt::whereNotNull('completed_at')
            ->with('applicant')
            ->orderBy('completed_at', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($e) => [
                'type' => 'exam',
                'message' => "{$e->applicant->first_name} {$e->applicant->last_name} completed exam (Score: {$e->total_score})",
                'timestamp' => $e->completed_at,
            ]);

        // Recent dispatch offers
        $recentOffers = DispatchOffer::with('applicant')
            ->orderBy('offered_at', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($o) => [
                'type' => 'dispatch',
                'message' => "Dispatch offer sent to {$o->applicant->first_name} {$o->applicant->last_name}",
                'timestamp' => $o->offered_at,
            ]);

        // Merge and sort all activities
        $allActivities = collect($recentApplications)
            ->merge($recentExams)
            ->merge($recentOffers)
            ->sortByDesc('timestamp')
            ->take(15)
            ->values();

        return $allActivities->toArray();
    }

    /**
     * Get funnel conversion statistics (pre-reg -> apprentice)
     */
    public function getFunnelStats(): JsonResponse
    {
        try {
            $preRegistrations = PreRegistration::count();
            $applications = Applicant::count();
            $validated = Applicant::where('is_validated', true)->count();
            $examTaken = ExamAttempt::whereNotNull('completed_at')->count();
            $examPassed = ExamAttempt::where('total_score', '>=', config('app.exam_passing_score', 70))->count();
            $ranked = Ranking::count();
            $offered = DispatchOffer::distinct('applicant_id')->count('applicant_id');
            $accepted = DispatchOffer::where('response_status', 'accepted')->count();
            $apprentices = Indenture::where('status', 'active')->count();

            $funnel = [
                [
                    'stage' => 'Pre-Registration',
                    'count' => $preRegistrations,
                    'percentage' => 100,
                ],
                [
                    'stage' => 'Applications',
                    'count' => $applications,
                    'percentage' => $preRegistrations > 0 ? round(($applications / $preRegistrations) * 100, 2) : 0,
                ],
                [
                    'stage' => 'Validated',
                    'count' => $validated,
                    'percentage' => $preRegistrations > 0 ? round(($validated / $preRegistrations) * 100, 2) : 0,
                ],
                [
                    'stage' => 'Exam Taken',
                    'count' => $examTaken,
                    'percentage' => $preRegistrations > 0 ? round(($examTaken / $preRegistrations) * 100, 2) : 0,
                ],
                [
                    'stage' => 'Exam Passed',
                    'count' => $examPassed,
                    'percentage' => $preRegistrations > 0 ? round(($examPassed / $preRegistrations) * 100, 2) : 0,
                ],
                [
                    'stage' => 'Ranked',
                    'count' => $ranked,
                    'percentage' => $preRegistrations > 0 ? round(($ranked / $preRegistrations) * 100, 2) : 0,
                ],
                [
                    'stage' => 'Offered',
                    'count' => $offered,
                    'percentage' => $preRegistrations > 0 ? round(($offered / $preRegistrations) * 100, 2) : 0,
                ],
                [
                    'stage' => 'Accepted',
                    'count' => $accepted,
                    'percentage' => $preRegistrations > 0 ? round(($accepted / $preRegistrations) * 100, 2) : 0,
                ],
                [
                    'stage' => 'Active Apprentices',
                    'count' => $apprentices,
                    'percentage' => $preRegistrations > 0 ? round(($apprentices / $preRegistrations) * 100, 2) : 0,
                ],
            ];

            return response()->json([
                'funnel' => $funnel,
                'overall_conversion_rate' => $preRegistrations > 0 ? round(($apprentices / $preRegistrations) * 100, 2) : 0,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching funnel stats: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to fetch funnel statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
