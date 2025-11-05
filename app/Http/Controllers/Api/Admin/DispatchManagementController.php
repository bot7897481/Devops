<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\DispatchRequest;
use App\Models\DispatchOffer;
use App\Models\Ranking;
use App\Models\Applicant;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DispatchManagementController extends Controller
{
    /**
     * Get all dispatch requests with filters
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = DispatchRequest::with(['chief', 'dispatchOffers'])
                ->orderBy('created_at', 'desc');

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by job type
            if ($request->has('job_type')) {
                $query->where('job_type', $request->job_type);
            }

            // Search by company name or city
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('company_name', 'like', "%{$search}%")
                        ->orWhere('job_location_city', 'like', "%{$search}%");
                });
            }

            $requests = $query->paginate($request->get('per_page', 20));

            // Add statistics to each request
            $requests->getCollection()->transform(function ($dispatchRequest) {
                $offers = $dispatchRequest->dispatchOffers;
                $dispatchRequest->offers_statistics = [
                    'total_sent' => $offers->count(),
                    'pending' => $offers->where('response_status', 'pending')->count(),
                    'accepted' => $offers->where('response_status', 'accepted')->count(),
                    'declined' => $offers->where('response_status', 'declined')->count(),
                    'positions_filled' => $offers->where('response_status', 'accepted')->count(),
                    'positions_remaining' => max(0, $dispatchRequest->positions_needed - $offers->where('response_status', 'accepted')->count()),
                ];
                return $dispatchRequest;
            });

            return response()->json($requests);
        } catch (\Exception $e) {
            \Log::error('Error fetching dispatch requests: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to fetch dispatch requests',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a specific dispatch request with detailed information
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $dispatchRequest = DispatchRequest::with([
                'chief',
                'dispatchOffers.applicant.ranking',
                'dispatchOffers.applicant.user'
            ])->findOrFail($id);

            return response()->json([
                'data' => $dispatchRequest,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Dispatch request not found',
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Error fetching dispatch request: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to fetch dispatch request',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Find suitable candidates for a dispatch request
     */
    public function findCandidates(Request $request, int $id): JsonResponse
    {
        try {
            $dispatchRequest = DispatchRequest::findOrFail($id);

            $validated = $request->validate([
                'limit' => 'sometimes|integer|min:1|max:100',
                'min_rank' => 'sometimes|integer|min:1',
                'max_rank' => 'sometimes|integer|min:1',
            ]);

            $limit = $validated['limit'] ?? 20;

            // Get already offered applicants for this request
            $alreadyOfferedIds = DispatchOffer::where('dispatch_request_id', $id)
                ->pluck('applicant_id')
                ->toArray();

            // Build query for eligible candidates
            $query = Ranking::with(['applicant.user', 'applicant.examAttempt'])
                ->whereHas('applicant', function ($q) {
                    // Only applicants who are validated and not currently dispatched
                    $q->where('is_validated', true)
                        ->whereIn('status', ['active', 'ranked']);
                })
                ->whereNotIn('applicant_id', $alreadyOfferedIds)
                ->orderBy('rank', 'asc');

            // Apply rank filters
            if (isset($validated['min_rank'])) {
                $query->where('rank', '>=', $validated['min_rank']);
            }
            if (isset($validated['max_rank'])) {
                $query->where('rank', '<=', $validated['max_rank']);
            }

            $candidates = $query->limit($limit)->get();

            // Format candidate data
            $formattedCandidates = $candidates->map(function ($ranking) {
                return [
                    'ranking_id' => $ranking->id,
                    'applicant_id' => $ranking->applicant_id,
                    'rank' => $ranking->rank,
                    'exam_score' => $ranking->exam_score,
                    'applicant' => [
                        'id' => $ranking->applicant->id,
                        'confirmation_number' => $ranking->applicant->confirmation_number,
                        'first_name' => $ranking->applicant->first_name,
                        'last_name' => $ranking->applicant->last_name,
                        'email' => $ranking->applicant->email,
                        'phone_primary' => $ranking->applicant->phone_primary,
                        'status' => $ranking->applicant->status,
                    ],
                ];
            });

            return response()->json([
                'dispatch_request' => [
                    'id' => $dispatchRequest->id,
                    'company_name' => $dispatchRequest->company_name,
                    'positions_needed' => $dispatchRequest->positions_needed,
                ],
                'candidates' => $formattedCandidates,
                'total_candidates' => $formattedCandidates->count(),
                'already_offered' => count($alreadyOfferedIds),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Dispatch request not found',
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Error finding candidates: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to find candidates',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send dispatch offer to applicant(s)
     */
    public function sendOffer(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'dispatch_request_id' => 'required|exists:dispatch_requests,id',
                'applicant_ids' => 'required|array|min:1',
                'applicant_ids.*' => 'required|exists:applicants,id',
                'response_deadline_hours' => 'sometimes|integer|min:24|max:168', // 1-7 days
            ]);

            $dispatchRequest = DispatchRequest::findOrFail($validated['dispatch_request_id']);

            // Check if dispatch request is still accepting offers
            if ($dispatchRequest->status === 'completed') {
                return response()->json([
                    'message' => 'This dispatch request is already completed',
                ], 400);
            }

            if ($dispatchRequest->status === 'cancelled') {
                return response()->json([
                    'message' => 'This dispatch request has been cancelled',
                ], 400);
            }

            $responseDeadlineHours = $validated['response_deadline_hours'] ?? 72; // Default 3 days
            $responseDeadline = now()->addHours($responseDeadlineHours);

            $offersCreated = [];
            $errors = [];

            DB::beginTransaction();

            try {
                foreach ($validated['applicant_ids'] as $applicantId) {
                    // Check if offer already exists
                    $existingOffer = DispatchOffer::where('dispatch_request_id', $dispatchRequest->id)
                        ->where('applicant_id', $applicantId)
                        ->first();

                    if ($existingOffer) {
                        $errors[] = "Offer already sent to applicant ID: {$applicantId}";
                        continue;
                    }

                    // Create the offer
                    $offer = DispatchOffer::create([
                        'dispatch_request_id' => $dispatchRequest->id,
                        'applicant_id' => $applicantId,
                        'offered_at' => now(),
                        'response_deadline' => $responseDeadline,
                        'response_status' => 'pending',
                    ]);

                    // Update applicant status
                    Applicant::where('id', $applicantId)->update([
                        'status' => 'offer_pending',
                    ]);

                    $offersCreated[] = $offer->id;

                    // TODO: Send email/SMS notification to applicant
                }

                // Update dispatch request status
                if ($dispatchRequest->status === 'pending') {
                    $dispatchRequest->update(['status' => 'in_progress']);
                }

                DB::commit();

                return response()->json([
                    'message' => count($offersCreated) . ' dispatch offer(s) sent successfully',
                    'offers_created' => count($offersCreated),
                    'errors' => $errors,
                    'offer_ids' => $offersCreated,
                ], 201);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error sending dispatch offers: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to send dispatch offers',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all dispatch offers with filters
     */
    public function getOffers(Request $request): JsonResponse
    {
        try {
            $query = DispatchOffer::with([
                'dispatchRequest.chief',
                'applicant.ranking',
                'applicant.user'
            ])->orderBy('offered_at', 'desc');

            // Filter by status
            if ($request->has('status')) {
                $query->where('response_status', $request->status);
            }

            // Filter by dispatch request
            if ($request->has('dispatch_request_id')) {
                $query->where('dispatch_request_id', $request->dispatch_request_id);
            }

            $offers = $query->paginate($request->get('per_page', 50));

            return response()->json($offers);
        } catch (\Exception $e) {
            \Log::error('Error fetching dispatch offers: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to fetch dispatch offers',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark dispatch request as completed
     */
    public function completeRequest(Request $request, int $id): JsonResponse
    {
        try {
            $dispatchRequest = DispatchRequest::with('dispatchOffers')->findOrFail($id);

            $acceptedCount = $dispatchRequest->dispatchOffers
                ->where('response_status', 'accepted')
                ->count();

            if ($acceptedCount < $dispatchRequest->positions_needed) {
                return response()->json([
                    'message' => "Only {$acceptedCount} out of {$dispatchRequest->positions_needed} positions filled. Cannot mark as completed.",
                    'allow_force' => true,
                ], 400);
            }

            $dispatchRequest->update(['status' => 'completed']);

            return response()->json([
                'message' => 'Dispatch request marked as completed',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Dispatch request not found',
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Error completing dispatch request: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to complete dispatch request',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get dispatch statistics
     */
    public function statistics(): JsonResponse
    {
        try {
            $totalRequests = DispatchRequest::count();
            $pendingRequests = DispatchRequest::where('status', 'pending')->count();
            $inProgressRequests = DispatchRequest::where('status', 'in_progress')->count();
            $completedRequests = DispatchRequest::where('status', 'completed')->count();

            $totalOffers = DispatchOffer::count();
            $pendingOffers = DispatchOffer::where('response_status', 'pending')->count();
            $acceptedOffers = DispatchOffer::where('response_status', 'accepted')->count();
            $declinedOffers = DispatchOffer::where('response_status', 'declined')->count();

            $totalPositions = DispatchRequest::sum('positions_needed');
            $filledPositions = DispatchRequest::whereHas('dispatchOffers', function ($q) {
                $q->where('response_status', 'accepted');
            })->with('dispatchOffers')->get()->sum(function ($req) {
                return $req->dispatchOffers->where('response_status', 'accepted')->count();
            });

            return response()->json([
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
                ],
                'positions' => [
                    'total_requested' => $totalPositions,
                    'filled' => $filledPositions,
                    'remaining' => max(0, $totalPositions - $filledPositions),
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching dispatch statistics: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to fetch statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
