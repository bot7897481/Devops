<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Indenture;
use App\Models\DispatchOffer;
use App\Models\Applicant;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class IndentureController extends Controller
{
    /**
     * Generate indenture contract for an accepted dispatch offer
     */
    public function generate(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'dispatch_offer_id' => 'required|exists:dispatch_offers,id',
                'term_length_years' => 'required|integer|min:3|max:5',
                'wage_schedule' => 'required|array',
                'wage_schedule.*.year' => 'required|integer',
                'wage_schedule.*.percentage' => 'required|numeric|min:40|max:100',
            ]);

            $dispatchOffer = DispatchOffer::with([
                'applicant.user',
                'dispatchRequest.chief'
            ])->findOrFail($validated['dispatch_offer_id']);

            // Verify offer was accepted
            if ($dispatchOffer->response_status !== 'accepted') {
                return response()->json([
                    'message' => 'Indenture can only be generated for accepted offers',
                ], 400);
            }

            // Check if indenture already exists
            $existingIndenture = Indenture::where('dispatch_offer_id', $dispatchOffer->id)->first();
            if ($existingIndenture) {
                return response()->json([
                    'message' => 'Indenture already exists for this dispatch offer',
                    'indenture_id' => $existingIndenture->id,
                ], 400);
            }

            $applicant = $dispatchOffer->applicant;
            $dispatchRequest = $dispatchOffer->dispatchRequest;

            // Generate PDF
            $pdfData = [
                'applicant' => [
                    'name' => $applicant->full_name,
                    'address' => $applicant->address_street . ', ' . $applicant->address_city . ', ' . $applicant->address_state . ' ' . $applicant->address_zip,
                    'phone' => $applicant->phone_primary,
                    'email' => $applicant->email,
                ],
                'employer' => [
                    'company_name' => $dispatchRequest->company_name,
                    'location' => $dispatchRequest->job_location_address . ', ' . $dispatchRequest->job_location_city . ', ' . $dispatchRequest->job_location_state,
                ],
                'union' => [
                    'name' => 'Local 39 Stationary Engineers',
                    'address' => '1620 South Loop Road, Alameda, CA 94502',
                ],
                'contract_details' => [
                    'start_date' => $dispatchRequest->start_date->format('F d, Y'),
                    'term_length_years' => $validated['term_length_years'],
                    'wage_schedule' => $validated['wage_schedule'],
                    'job_description' => $dispatchRequest->job_description,
                ],
                'generated_date' => now()->format('F d, Y'),
            ];

            $pdf = Pdf::loadView('pdf.indenture', $pdfData);

            // Save PDF
            $fileName = 'indenture_' . $applicant->confirmation_number . '_' . time() . '.pdf';
            $filePath = 'private/indentures/' . $fileName;
            Storage::put($filePath, $pdf->output());

            // Create indenture record
            $indenture = Indenture::create([
                'dispatch_offer_id' => $dispatchOffer->id,
                'applicant_id' => $applicant->id,
                'company_name' => $dispatchRequest->company_name,
                'start_date' => $dispatchRequest->start_date,
                'term_length_years' => $validated['term_length_years'],
                'wage_schedule' => $validated['wage_schedule'],
                'document_template_path' => $filePath,
                'status' => 'draft',
                'entered_in_unionnet' => false,
            ]);

            // Update applicant status
            $applicant->update(['status' => 'indenture_pending']);

            return response()->json([
                'message' => 'Indenture contract generated successfully',
                'data' => $indenture,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error generating indenture: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to generate indenture',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all indentures with filters
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Indenture::with([
                'applicant.user',
                'dispatchOffer.dispatchRequest'
            ])->orderBy('created_at', 'desc');

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by UnionNet entry status
            if ($request->has('entered_in_unionnet')) {
                $query->where('entered_in_unionnet', $request->boolean('entered_in_unionnet'));
            }

            // Search by applicant name or company
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('company_name', 'like', "%{$search}%")
                        ->orWhereHas('applicant', function ($aq) use ($search) {
                            $aq->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('confirmation_number', 'like', "%{$search}%");
                        });
                });
            }

            $indentures = $query->paginate($request->get('per_page', 20));

            return response()->json($indentures);
        } catch (\Exception $e) {
            \Log::error('Error fetching indentures: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to fetch indentures',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a specific indenture
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $indenture = Indenture::with([
                'applicant.user',
                'dispatchOffer.dispatchRequest.chief'
            ])->findOrFail($id);

            return response()->json([
                'data' => $indenture,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Indenture not found',
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Error fetching indenture: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to fetch indenture',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download indenture PDF
     */
    public function download(Request $request, int $id)
    {
        try {
            $indenture = Indenture::findOrFail($id);

            if (!Storage::exists($indenture->document_template_path)) {
                return response()->json([
                    'message' => 'Indenture document not found',
                ], 404);
            }

            return Storage::download($indenture->document_template_path);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Indenture not found',
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Error downloading indenture: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to download indenture',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark signature received (apprentice, employer, or union)
     */
    public function markSigned(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'signature_type' => 'required|in:apprentice,employer,union',
            ]);

            $indenture = Indenture::findOrFail($id);

            $signatureField = match($validated['signature_type']) {
                'apprentice' => 'apprentice_signed_at',
                'employer' => 'employer_signed_at',
                'union' => 'union_signed_at',
            };

            $indenture->update([
                $signatureField => now(),
            ]);

            // If all signatures are collected, update status
            if ($indenture->isFullySigned()) {
                $indenture->update(['status' => 'fully_signed']);

                // Update applicant status
                $indenture->applicant->update(['status' => 'apprentice']);
            }

            return response()->json([
                'message' => ucfirst($validated['signature_type']) . ' signature marked as received',
                'data' => $indenture->fresh(),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Indenture not found',
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error marking signature: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to mark signature',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark indenture as entered in UnionNet
     */
    public function markEnteredInUnionNet(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'unionnet_id' => 'required|string|max:50',
            ]);

            $indenture = Indenture::findOrFail($id);

            // Verify all signatures are collected
            if (!$indenture->isFullySigned()) {
                return response()->json([
                    'message' => 'All signatures must be collected before entering in UnionNet',
                ], 400);
            }

            $indenture->update([
                'entered_in_unionnet' => true,
                'unionnet_id' => $validated['unionnet_id'],
                'status' => 'active',
            ]);

            return response()->json([
                'message' => 'Indenture marked as entered in UnionNet',
                'data' => $indenture->fresh(),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Indenture not found',
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error marking UnionNet entry: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to mark UnionNet entry',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get indenture statistics
     */
    public function statistics(): JsonResponse
    {
        try {
            $totalIndentures = Indenture::count();
            $draft = Indenture::where('status', 'draft')->count();
            $fullySigned = Indenture::where('status', 'fully_signed')->count();
            $active = Indenture::where('status', 'active')->count();
            $enteredInUnionNet = Indenture::where('entered_in_unionnet', true)->count();

            return response()->json([
                'total' => $totalIndentures,
                'by_status' => [
                    'draft' => $draft,
                    'fully_signed' => $fullySigned,
                    'active' => $active,
                ],
                'entered_in_unionnet' => $enteredInUnionNet,
                'pending_unionnet' => $fullySigned - $enteredInUnionNet,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching indenture statistics: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to fetch statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
