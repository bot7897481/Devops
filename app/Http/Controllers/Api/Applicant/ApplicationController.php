<?php

namespace App\Http\Controllers\Api\Applicant;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\User;
use App\Notifications\ApplicationSubmitted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;

class ApplicationController extends Controller
{
    /**
     * Submit application (multi-step form data)
     */
    public function store(Request $request)
    {
        // Check if applications are open
        $applicationOpenDate = config('app.application_open_date');
        if (now()->lt($applicationOpenDate)) {
            return response()->json([
                'message' => 'Applications are not open yet.',
                'opens_at' => $applicationOpenDate
            ], 403);
        }

        // Check if user already has an application
        $existingApplication = Applicant::where('user_id', $request->user()->id)->first();
        if ($existingApplication) {
            return response()->json([
                'message' => 'You have already submitted an application.',
                'application' => $existingApplication
            ], 409);
        }

        // Validate all required fields
        $validated = $request->validate([
            // Step 1: Personal Information
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'dob' => 'required|date|before:today',
            'ssn_last4' => 'required|digits:4',
            'address_street' => 'required|string|max:255',
            'address_city' => 'required|string|max:255',
            'address_state' => 'required|string|size:2',
            'address_zip' => 'required|string|max:10',
            'mailing_address' => 'nullable|array',
            'phone_primary' => 'required|string|max:20',
            'phone_alternate' => 'nullable|string|max:20',

            // Step 2: Educational Background
            'high_school_name' => 'required|string|max:255',
            'hs_graduation_date' => 'required|date|before:today',
            'diploma_path' => 'required|string', // File already uploaded
            'post_secondary' => 'nullable|array',

            // Step 3: Work Experience
            'current_employment_status' => 'nullable|string',
            'work_experience' => 'nullable|array',

            // Step 4: Identification
            'id_document_front_path' => 'required|string', // File already uploaded
            'id_document_back_path' => 'nullable|string', // File already uploaded

            // Step 5: Certifications
            'certify_accuracy' => 'required|accepted',
            'agree_to_terms' => 'required|accepted',
            'digital_signature' => 'required|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            // Encrypt sensitive data
            $encryptedSSN = Crypt::encryptString($validated['ssn_last4']);

            // Generate unique confirmation number
            do {
                $confirmationNumber = Applicant::generateConfirmationNumber();
            } while (Applicant::where('confirmation_number', $confirmationNumber)->exists());

            // Create applicant record
            $applicant = Applicant::create([
                'user_id' => $request->user()->id,
                'confirmation_number' => $confirmationNumber,
                'first_name' => $validated['first_name'],
                'middle_name' => $validated['middle_name'] ?? null,
                'last_name' => $validated['last_name'],
                'dob' => $validated['dob'],
                'ssn_last4' => $encryptedSSN,
                'address_street' => $validated['address_street'],
                'address_city' => $validated['address_city'],
                'address_state' => $validated['address_state'],
                'address_zip' => $validated['address_zip'],
                'mailing_address' => $validated['mailing_address'] ?? null,
                'phone_primary' => $validated['phone_primary'],
                'phone_alternate' => $validated['phone_alternate'] ?? null,
                'email' => $request->user()->email,
                'high_school_name' => $validated['high_school_name'],
                'hs_graduation_date' => $validated['hs_graduation_date'],
                'diploma_path' => $validated['diploma_path'],
                'id_document_front_path' => $validated['id_document_front_path'],
                'id_document_back_path' => $validated['id_document_back_path'] ?? null,
                'work_experience' => $validated['work_experience'] ?? null,
                'application_timestamp' => now(), // CRITICAL for ranking
                'status' => 'pending',
            ]);

            DB::commit();

            // Send confirmation email
            $request->user()->notify(new ApplicationSubmitted($applicant));

            return response()->json([
                'message' => 'Application submitted successfully!',
                'data' => [
                    'confirmation_number' => $applicant->confirmation_number,
                    'application_timestamp' => $applicant->application_timestamp,
                    'status' => $applicant->status,
                    'next_steps' => 'Please bring your physical documents to the validation location for in-person verification.',
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to submit application. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Upload a document (called during form steps)
     */
    public function uploadDocument(Request $request)
    {
        $request->validate([
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB max
            'document_type' => 'required|in:diploma,id_front,id_back,other',
        ]);

        try {
            $file = $request->file('document');
            $documentType = $request->input('document_type');

            // Generate unique filename
            $filename = time() . '_' . $documentType . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

            // Store in private storage
            $path = $file->storeAs('applications/documents', $filename, 'private');

            return response()->json([
                'message' => 'Document uploaded successfully',
                'path' => $path,
                'filename' => $filename,
                'size' => $file->getSize(),
                'type' => $file->getMimeType(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to upload document',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get application status
     */
    public function getStatus(Request $request)
    {
        $applicant = Applicant::where('user_id', $request->user()->id)
            ->with(['examAssignment.examSession', 'ranking'])
            ->first();

        if (!$applicant) {
            return response()->json([
                'message' => 'No application found',
                'has_application' => false,
            ]);
        }

        return response()->json([
            'has_application' => true,
            'data' => [
                'confirmation_number' => $applicant->confirmation_number,
                'full_name' => $applicant->full_name,
                'application_timestamp' => $applicant->application_timestamp,
                'status' => $applicant->status,
                'is_validated' => $applicant->is_validated,
                'validation_timestamp' => $applicant->validation_timestamp,
                'exam_session' => $applicant->examAssignment?->examSession,
                'rank' => $applicant->ranking?->rank,
            ]
        ]);
    }
}
