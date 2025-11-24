<?php

namespace App\Http\Controllers\Api\Applicant;

use App\Http\Controllers\Controller;
use App\Models\DispatchOffer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class DispatchOfferController extends Controller
{
    /**
     * Get all dispatch offers for the authenticated applicant
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $applicant = $user->applicant;

            if (!$applicant) {
                return response()->json([
                    'message' => 'No application found',
                ], 404);
            }

            $query = DispatchOffer::where('applicant_id', $applicant->id)
                ->with(['dispatchRequest.chief'])
                ->orderBy('offered_at', 'desc');

            // Filter by status
            if ($request->has('status')) {
                $query->where('response_status', $request->status);
            }

            $offers = $query->get();

            // Add expired status to pending offers
            $offers->transform(function ($offer) {
                $offer->is_expired = $offer->isExpired();
                return $offer;
            });

            return response()->json([
                'data' => $offers,
                'statistics' => [
                    'total' => $offers->count(),
                    'pending' => $offers->where('response_status', 'pending')->count(),
                    'accepted' => $offers->where('response_status', 'accepted')->count(),
                    'declined' => $offers->where('response_status', 'declined')->count(),
                    'expired' => $offers->filter(fn($o) => $o->is_expired)->count(),
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching dispatch offers: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to fetch dispatch offers',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Respond to a dispatch offer (accept or decline)
     */
    public function respond(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'response' => ['required', Rule::in(['accept', 'decline'])],
                'decline_reason' => 'required_if:response,decline|nullable|string|max:500',
            ]);

            $user = $request->user();
            $applicant = $user->applicant;

            if (!$applicant) {
                return response()->json([
                    'message' => 'No application found',
                ], 404);
            }

            $offer = DispatchOffer::where('id', $id)
                ->where('applicant_id', $applicant->id)
                ->with('dispatchRequest')
                ->firstOrFail();

            // Check if already responded
            if ($offer->response_status !== 'pending') {
                return response()->json([
                    'message' => 'This offer has already been ' . $offer->response_status,
                ], 400);
            }

            // Check if expired
            if ($offer->isExpired()) {
                $offer->update(['response_status' => 'expired']);
                return response()->json([
                    'message' => 'This offer has expired',
                ], 400);
            }

            $response = $validated['response'];

            // Update the offer
            $offer->update([
                'response_status' => $response === 'accept' ? 'accepted' : 'declined',
                'response_at' => now(),
                'decline_reason' => $response === 'decline' ? $validated['decline_reason'] : null,
            ]);

            // If accepted, update applicant status
            if ($response === 'accept') {
                $applicant->update([
                    'status' => 'dispatched',
                ]);

                // TODO: Create indenture record
                // TODO: Notify admins and chief
            } else {
                // TODO: Notify admins that candidate declined
            }

            $message = $response === 'accept'
                ? 'Offer accepted successfully! The union will contact you with next steps.'
                : 'Offer declined. Thank you for your response.';

            return response()->json([
                'message' => $message,
                'data' => $offer->fresh(),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Dispatch offer not found',
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error responding to dispatch offer: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to respond to offer',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a specific dispatch offer details
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();
            $applicant = $user->applicant;

            if (!$applicant) {
                return response()->json([
                    'message' => 'No application found',
                ], 404);
            }

            $offer = DispatchOffer::where('id', $id)
                ->where('applicant_id', $applicant->id)
                ->with(['dispatchRequest.chief'])
                ->firstOrFail();

            $offer->is_expired = $offer->isExpired();

            return response()->json([
                'data' => $offer,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Dispatch offer not found',
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Error fetching dispatch offer: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to fetch dispatch offer',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
