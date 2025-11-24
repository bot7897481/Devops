<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\PreRegistration;
use App\Notifications\PreRegistrationConfirmation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class PreRegistrationController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:pre_registrations,email',
            'phone' => 'required|string|max:20',
            'document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'g-recaptcha-response' => 'required', // reCAPTCHA validation
        ]);

        // Handle document upload if provided
        $documentPath = null;
        if ($request->hasFile('document')) {
            $documentPath = $request->file('document')->store('pre-registrations', 'private');
        }

        // Generate unique reference number
        $referenceNumber = PreRegistration::generateReferenceNumber();

        $preRegistration = PreRegistration::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'document_path' => $documentPath,
            'reference_number' => $referenceNumber,
        ]);

        // Send confirmation email
        Notification::route('mail', $preRegistration->email)
            ->notify(new PreRegistrationConfirmation($preRegistration));

        return response()->json([
            'message' => 'Pre-registration successful',
            'reference_number' => $referenceNumber,
            'data' => $preRegistration,
        ], 201);
    }
}
