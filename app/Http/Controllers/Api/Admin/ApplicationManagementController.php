<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Notifications\ApplicationValidated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ApplicationsExport;

class ApplicationManagementController extends Controller
{
    /**
     * List all applications with filters and pagination
     */
    public function index(Request $request)
    {
        $query = Applicant::query()->with(['user', 'validationAdmin']);

        // Filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('is_validated')) {
            $query->where('is_validated', $request->boolean('is_validated'));
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('confirmation_number', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'application_timestamp');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 15);
        $applications = $query->paginate($perPage);

        return response()->json($applications);
    }

    /**
     * Get single application details
     */
    public function show($id)
    {
        $applicant = Applicant::with(['user', 'validationAdmin', 'examAssignment', 'ranking'])
            ->findOrFail($id);

        return response()->json([
            'data' => $applicant,
            'documents' => [
                'diploma' => $applicant->diploma_path,
                'id_front' => $applicant->id_document_front_path,
                'id_back' => $applicant->id_document_back_path,
                'photo' => $applicant->photo_biometric_path,
            ]
        ]);
    }

    /**
     * Validate an application (in-person verification)
     */
    public function validate(Request $request, $id)
    {
        $validated = $request->validate([
            'documents_verified' => 'required|boolean',
            'id_verified' => 'required|boolean',
            'notes' => 'nullable|string|max:1000',
        ]);

        $applicant = Applicant::findOrFail($id);

        if ($applicant->is_validated) {
            return response()->json([
                'message' => 'Application is already validated'
            ], 409);
        }

        if (!$validated['documents_verified'] || !$validated['id_verified']) {
            return response()->json([
                'message' => 'Both documents and ID must be verified to validate application'
            ], 422);
        }

        // Update applicant
        $applicant->update([
            'is_validated' => true,
            'validation_timestamp' => now(), // CRITICAL for ranking tiebreaker
            'validation_admin_id' => $request->user()->id,
            'status' => 'validated',
        ]);

        // Send notification to applicant
        $applicant->user->notify(new ApplicationValidated($applicant));

        return response()->json([
            'message' => 'Application validated successfully',
            'data' => $applicant
        ]);
    }

    /**
     * Capture biometric data (photo/fingerprint)
     */
    public function captureBiometric(Request $request, $id)
    {
        $request->validate([
            'photo' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'biometric_type' => 'required|in:photo,fingerprint',
        ]);

        $applicant = Applicant::findOrFail($id);

        try {
            if ($request->biometric_type === 'photo' && $request->hasFile('photo')) {
                $file = $request->file('photo');
                $filename = 'biometric_' . $applicant->id . '_' . time() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('applications/biometrics', $filename, 'private');

                $applicant->update([
                    'photo_biometric_path' => $path
                ]);
            }

            // For fingerprint, you would integrate with hardware
            // This is a placeholder for future implementation
            if ($request->biometric_type === 'fingerprint' && $request->has('fingerprint_data')) {
                // Hash and encrypt fingerprint data
                $fingerprintHash = hash('sha256', $request->fingerprint_data);
                $applicant->update([
                    'fingerprint_hash' => encrypt($fingerprintHash)
                ]);
            }

            return response()->json([
                'message' => 'Biometric data captured successfully',
                'data' => $applicant
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to capture biometric data',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Export applications to Excel
     */
    public function export(Request $request)
    {
        $query = Applicant::query();

        // Apply same filters as index
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('is_validated')) {
            $query->where('is_validated', $request->boolean('is_validated'));
        }

        $applications = $query->get();

        return Excel::download(new ApplicationsExport($applications), 'applications_' . date('Y-m-d') . '.xlsx');
    }

    /**
     * Download applicant document
     */
    public function downloadDocument($id, $type)
    {
        $applicant = Applicant::findOrFail($id);

        $path = match($type) {
            'diploma' => $applicant->diploma_path,
            'id_front' => $applicant->id_document_front_path,
            'id_back' => $applicant->id_document_back_path,
            'photo' => $applicant->photo_biometric_path,
            default => null
        };

        if (!$path || !Storage::disk('private')->exists($path)) {
            return response()->json(['message' => 'Document not found'], 404);
        }

        return Storage::disk('private')->download($path);
    }
}
