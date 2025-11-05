<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Public\PreRegistrationController;
use App\Http\Controllers\Api\Public\CountdownController;
use App\Http\Controllers\Api\Applicant\ApplicationController;
use App\Http\Controllers\Api\Applicant\ExamController;
use App\Http\Controllers\Api\Applicant\RankingController as ApplicantRankingController;
use App\Http\Controllers\Api\Applicant\DispatchOfferController as ApplicantDispatchOfferController;
use App\Http\Controllers\Api\Chief\DispatchRequestController as ChiefDispatchRequestController;
use App\Http\Controllers\Api\Admin\ApplicationManagementController;
use App\Http\Controllers\Api\Admin\ExamManagementController;
use App\Http\Controllers\Api\Admin\RankingController as AdminRankingController;
use App\Http\Controllers\Api\Admin\DispatchManagementController;
use App\Http\Controllers\Api\Admin\IndentureController;
use App\Http\Controllers\Api\Admin\AnalyticsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes (no authentication required)
Route::prefix('public')->group(function () {
    Route::get('/countdown', [CountdownController::class, 'getCountdown']);
    Route::post('/pre-register', [PreRegistrationController::class, 'store']);
});

// Authentication routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);
    });
});

// Applicant routes
Route::middleware(['auth:sanctum', 'role:applicant'])->prefix('applicant')->group(function () {
    Route::post('/application', [ApplicationController::class, 'store']);
    Route::post('/application/upload', [ApplicationController::class, 'uploadDocument']);
    Route::get('/application/status', [ApplicationController::class, 'getStatus']);

    Route::get('/exam/session', [ExamController::class, 'getSession']);
    Route::post('/exam/start', [ExamController::class, 'start']);
    Route::post('/exam/answer', [ExamController::class, 'submitAnswer']);
    Route::post('/exam/complete-section', [ExamController::class, 'completeSection']);
    Route::get('/exam/status', [ExamController::class, 'getStatus']);

    Route::get('/ranking', [ApplicantRankingController::class, 'getMyRanking']);

    Route::get('/dispatch-offers', [ApplicantDispatchOfferController::class, 'index']);
    Route::post('/dispatch-offers/{id}/respond', [ApplicantDispatchOfferController::class, 'respond']);
});

// Chief routes
Route::middleware(['auth:sanctum', 'role:chief'])->prefix('chief')->group(function () {
    Route::get('/dispatch-requests', [ChiefDispatchRequestController::class, 'index']);
    Route::post('/dispatch-requests', [ChiefDispatchRequestController::class, 'store']);
    Route::get('/dispatch-requests/{id}', [ChiefDispatchRequestController::class, 'show']);
});

// Admin routes
Route::middleware(['auth:sanctum', 'role:admin|super_admin'])->prefix('admin')->group(function () {
    // Application Management
    Route::get('/applications', [ApplicationManagementController::class, 'index']);
    Route::get('/applications/{id}', [ApplicationManagementController::class, 'show']);
    Route::post('/applications/{id}/validate', [ApplicationManagementController::class, 'validate']);
    Route::post('/applications/{id}/capture-biometric', [ApplicationManagementController::class, 'captureBiometric']);
    Route::get('/applications/export', [ApplicationManagementController::class, 'export']);

    // Exam Management
    Route::post('/exam-sessions', [ExamManagementController::class, 'createSession']);
    Route::get('/exam-sessions', [ExamManagementController::class, 'getSessions']);
    Route::get('/exam-results', [ExamManagementController::class, 'getResults']);
    Route::post('/exam-results/export', [ExamManagementController::class, 'exportResults']);

    // Question Bank
    Route::get('/questions', [ExamManagementController::class, 'getQuestions']);
    Route::post('/questions', [ExamManagementController::class, 'createQuestion']);
    Route::put('/questions/{id}', [ExamManagementController::class, 'updateQuestion']);
    Route::delete('/questions/{id}', [ExamManagementController::class, 'deleteQuestion']);
    Route::post('/questions/import', [ExamManagementController::class, 'importQuestions']);

    // Rankings
    Route::post('/rankings/generate', [AdminRankingController::class, 'generate']);
    Route::get('/rankings', [AdminRankingController::class, 'index']);
    Route::get('/rankings/export', [AdminRankingController::class, 'export']);

    // Dispatch Management
    Route::get('/dispatch-requests', [DispatchManagementController::class, 'index']);
    Route::get('/dispatch-requests/{id}', [DispatchManagementController::class, 'show']);
    Route::post('/dispatch-requests/{id}/find-candidates', [DispatchManagementController::class, 'findCandidates']);
    Route::post('/dispatch-offers', [DispatchManagementController::class, 'sendOffer']);
    Route::get('/dispatch-offers', [DispatchManagementController::class, 'getOffers']);

    // Indenture Management
    Route::post('/indentures/generate', [IndentureController::class, 'generate']);
    Route::get('/indentures', [IndentureController::class, 'index']);
    Route::post('/indentures/{id}/mark-unionnet', [IndentureController::class, 'markEnteredInUnionNet']);

    // Analytics & Reports
    Route::get('/analytics/dashboard', [AnalyticsController::class, 'getDashboard']);
    Route::get('/analytics/pre-registrations', [AnalyticsController::class, 'getPreRegistrationStats']);
    Route::get('/analytics/applications', [AnalyticsController::class, 'getApplicationStats']);
    Route::get('/analytics/exams', [AnalyticsController::class, 'getExamStats']);
    Route::get('/analytics/dispatches', [AnalyticsController::class, 'getDispatchStats']);
});
