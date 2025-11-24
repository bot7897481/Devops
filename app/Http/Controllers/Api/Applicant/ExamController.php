<?php

namespace App\Http\Controllers\Api\Applicant;

use App\Http\Controllers\Controller;
use App\Models\ExamAssignment;
use App\Models\ExamAttempt;
use App\Models\ExamQuestion;
use App\Models\ExamResponse;
use App\Models\Applicant;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ExamController extends Controller
{
    public function getAssignment(Request $request): JsonResponse
    {
        $applicant = $request->user()->applicant;

        $assignment = ExamAssignment::where('applicant_id', $applicant->id)
            ->with('examSession')
            ->first();

        if (!$assignment) {
            return response()->json([
                'message' => 'No exam assignment found',
            ], 404);
        }

        return response()->json(['data' => $assignment]);
    }

    public function checkIn(Request $request): JsonResponse
    {
        $applicant = $request->user()->applicant;

        $assignment = ExamAssignment::where('applicant_id', $applicant->id)
            ->with('examSession')
            ->firstOrFail();

        if ($assignment->status !== 'assigned') {
            return response()->json([
                'message' => 'Already checked in or exam in progress',
            ], 422);
        }

        // In production, admin should verify check-in
        // For now, allow self-check-in
        $assignment->checkIn($request->user()->id);

        return response()->json([
            'message' => 'Checked in successfully',
            'data' => $assignment,
        ]);
    }

    public function startExam(Request $request): JsonResponse
    {
        $applicant = $request->user()->applicant;

        // Check if already has an attempt
        $existingAttempt = ExamAttempt::where('applicant_id', $applicant->id)->first();
        if ($existingAttempt) {
            return response()->json([
                'message' => 'You have already started an exam',
                'data' => $existingAttempt,
            ], 422);
        }

        $assignment = ExamAssignment::where('applicant_id', $applicant->id)
            ->where('status', 'checked_in')
            ->firstOrFail();

        // Create exam attempt
        $attempt = ExamAttempt::create([
            'applicant_id' => $applicant->id,
            'exam_session_id' => $assignment->exam_session_id,
            'started_at' => now(),
        ]);

        // Update assignment status
        $assignment->startExam();

        // Generate randomized questions for each section
        $sections = ['reading', 'math_computation', 'applied_math', 'language', 'aptitude'];
        $questionCounts = [25, 15, 25, 25, 36];

        $allQuestions = [];

        foreach ($sections as $index => $section) {
            $questions = ExamQuestion::where('section', $section)
                ->inRandomOrder()
                ->limit($questionCounts[$index])
                ->get();

            // Create response records
            foreach ($questions as $question) {
                ExamResponse::create([
                    'exam_attempt_id' => $attempt->id,
                    'question_id' => $question->id,
                ]);

                $question->incrementUsage();
            }

            $allQuestions[$section] = $questions;
        }

        return response()->json([
            'message' => 'Exam started successfully',
            'data' => [
                'attempt' => $attempt,
                'questions' => $allQuestions,
            ],
        ]);
    }

    public function getQuestions(Request $request, int $section): JsonResponse
    {
        $applicant = $request->user()->applicant;

        $attempt = ExamAttempt::where('applicant_id', $applicant->id)
            ->where('completed_at', null)
            ->firstOrFail();

        $sectionName = $this->getSectionName($section);

        $questions = ExamResponse::where('exam_attempt_id', $attempt->id)
            ->whereHas('question', fn($q) => $q->where('section', $sectionName))
            ->with(['question' => function($q) {
                // Don't reveal correct answer
                $q->select('id', 'section', 'question_text', 'question_image_path', 
                          'option_a', 'option_b', 'option_c', 'option_d');
            }])
            ->get();

        return response()->json([
            'data' => [
                'section' => $section,
                'section_name' => $sectionName,
                'time_limit' => $this->getSectionTimeLimit($sectionName),
                'questions' => $questions,
            ],
        ]);
    }

    public function submitAnswer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'response_id' => 'required|exists:exam_responses,id',
            'selected_answer' => 'required|in:a,b,c,d',
            'time_spent' => 'required|integer|min:0',
            'marked_for_review' => 'boolean',
        ]);

        $applicant = $request->user()->applicant;

        $response = ExamResponse::with(['examAttempt', 'question'])
            ->findOrFail($validated['response_id']);

        // Verify this response belongs to the user's attempt
        if ($response->examAttempt->applicant_id !== $applicant->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $response->recordAnswer($validated['selected_answer'], $validated['time_spent']);
        
        if (isset($validated['marked_for_review'])) {
            $response->marked_for_review = $validated['marked_for_review'];
            $response->save();
        }

        return response()->json([
            'message' => 'Answer recorded successfully',
            'data' => $response,
        ]);
    }

    public function completeSection(Request $request, int $section): JsonResponse
    {
        $validated = $request->validate([
            'time_spent' => 'required|integer|min:0',
        ]);

        $applicant = $request->user()->applicant;

        $attempt = ExamAttempt::where('applicant_id', $applicant->id)
            ->where('completed_at', null)
            ->firstOrFail();

        // Update section time
        $timeField = "section_{$section}_time";
        $attempt->update([$timeField => $validated['time_spent']]);

        return response()->json([
            'message' => 'Section completed',
            'data' => $attempt,
        ]);
    }

    public function completeExam(Request $request): JsonResponse
    {
        $applicant = $request->user()->applicant;

        $attempt = ExamAttempt::where('applicant_id', $applicant->id)
            ->where('completed_at', null)
            ->firstOrFail();

        // Calculate scores
        $attempt->calculateScore();

        // Mark as completed
        $attempt->update(['completed_at' => now()]);

        // Update assignment
        $assignment = ExamAssignment::where('applicant_id', $applicant->id)->first();
        $assignment->completeExam();

        // Update applicant status
        $applicant->update(['status' => 'exam_completed']);

        // TODO: Send notification email

        return response()->json([
            'message' => 'Exam completed successfully',
            'data' => [
                'completed_at' => $attempt->completed_at,
                'passing_score' => config('app.exam_passing_score', 70),
            ],
        ]);
    }

    private function getSectionName(int $sectionNumber): string
    {
        return match($sectionNumber) {
            1 => 'reading',
            2 => 'math_computation',
            3 => 'applied_math',
            4 => 'language',
            5 => 'aptitude',
            default => 'reading',
        };
    }

    private function getSectionTimeLimit(string $section): int
    {
        return match($section) {
            'reading' => 25,
            'math_computation' => 9,
            'applied_math' => 25,
            'language' => 18,
            'aptitude' => 20,
            default => 20,
        };
    }
}
