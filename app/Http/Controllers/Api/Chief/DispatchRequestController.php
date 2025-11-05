<?php

namespace App\Http\Controllers\Api\Chief;

use App\Http\Controllers\Controller;
use App\Models\DispatchRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class DispatchRequestController extends Controller
{
    /**
     * Get all dispatch requests for the authenticated chief
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $query = DispatchRequest::where('chief_user_id', $user->id)
                ->with(['dispatchOffers.applicant'])
                ->orderBy('created_at', 'desc');

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $requests = $query->paginate($request->get('per_page', 20));

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
     * Create a new dispatch request
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'company_name' => 'required|string|max:255',
                'job_location_address' => 'required|string|max:255',
                'job_location_city' => 'required|string|max:100',
                'job_location_state' => 'required|string|max:2',
                'job_type' => ['required', Rule::in(['commercial', 'industrial', 'residential', 'institutional'])],
                'start_date' => 'required|date|after_or_equal:today',
                'positions_needed' => 'required|integer|min:1|max:100',
                'job_description' => 'required|string|max:2000',
                'special_requirements' => 'nullable|string|max:1000',
            ]);

            $dispatchRequest = DispatchRequest::create([
                'chief_user_id' => $request->user()->id,
                'company_name' => $validated['company_name'],
                'job_location_address' => $validated['job_location_address'],
                'job_location_city' => $validated['job_location_city'],
                'job_location_state' => $validated['job_location_state'],
                'job_type' => $validated['job_type'],
                'start_date' => $validated['start_date'],
                'positions_needed' => $validated['positions_needed'],
                'job_description' => $validated['job_description'],
                'special_requirements' => $validated['special_requirements'] ?? null,
                'status' => 'pending',
            ]);

            // TODO: Send notification to admins about new dispatch request

            return response()->json([
                'message' => 'Dispatch request submitted successfully',
                'data' => $dispatchRequest,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error creating dispatch request: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to create dispatch request',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a specific dispatch request
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();

            $dispatchRequest = DispatchRequest::where('id', $id)
                ->where('chief_user_id', $user->id)
                ->with(['dispatchOffers.applicant.ranking'])
                ->firstOrFail();

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
     * Update a dispatch request (only if still pending)
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();

            $dispatchRequest = DispatchRequest::where('id', $id)
                ->where('chief_user_id', $user->id)
                ->firstOrFail();

            // Only allow updates if status is still pending
            if ($dispatchRequest->status !== 'pending') {
                return response()->json([
                    'message' => 'Cannot update dispatch request with status: ' . $dispatchRequest->status,
                ], 400);
            }

            $validated = $request->validate([
                'company_name' => 'sometimes|string|max:255',
                'job_location_address' => 'sometimes|string|max:255',
                'job_location_city' => 'sometimes|string|max:100',
                'job_location_state' => 'sometimes|string|max:2',
                'job_type' => ['sometimes', Rule::in(['commercial', 'industrial', 'residential', 'institutional'])],
                'start_date' => 'sometimes|date|after_or_equal:today',
                'positions_needed' => 'sometimes|integer|min:1|max:100',
                'job_description' => 'sometimes|string|max:2000',
                'special_requirements' => 'nullable|string|max:1000',
            ]);

            $dispatchRequest->update($validated);

            return response()->json([
                'message' => 'Dispatch request updated successfully',
                'data' => $dispatchRequest,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Dispatch request not found',
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error updating dispatch request: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to update dispatch request',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel a dispatch request
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();

            $dispatchRequest = DispatchRequest::where('id', $id)
                ->where('chief_user_id', $user->id)
                ->firstOrFail();

            if (in_array($dispatchRequest->status, ['completed', 'cancelled'])) {
                return response()->json([
                    'message' => 'Cannot cancel dispatch request with status: ' . $dispatchRequest->status,
                ], 400);
            }

            $dispatchRequest->update(['status' => 'cancelled']);

            // TODO: Notify admins and affected applicants

            return response()->json([
                'message' => 'Dispatch request cancelled successfully',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Dispatch request not found',
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Error cancelling dispatch request: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to cancel dispatch request',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
