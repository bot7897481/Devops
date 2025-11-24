<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamSession;
use App\Models\ExamAssignment;
use App\Models\Applicant;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ExamSessionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $sessions = ExamSession::query()
            ->when($request->status, fn($q, $status) => $q->where('status', $status))
            ->when($request->date, fn($q, $date) => $q->where('date', $date))
            ->with('assignments')
            ->orderBy('date')
            ->orderBy('start_time')
            ->paginate(20);

        return response()->json($sessions);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'time_slot' => 'required|in:morning,afternoon,evening',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'location' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1|max:200',
        ]);

        $session = ExamSession::create($validated);

        return response()->json([
            'message' => 'Exam session created successfully',
            'data' => $session,
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $session = ExamSession::with(['assignments.applicant.user'])
            ->findOrFail($id);

        return response()->json(['data' => $session]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $session = ExamSession::findOrFail($id);

        $validated = $request->validate([
            'date' => 'sometimes|date',
            'time_slot' => 'sometimes|in:morning,afternoon,evening',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i|after:start_time',
            'location' => 'sometimes|string|max:255',
            'capacity' => 'sometimes|integer|min:1|max:200',
            'status' => 'sometimes|in:scheduled,in_progress,completed',
        ]);

        $session->update($validated);

        return response()->json([
            'message' => 'Exam session updated successfully',
            'data' => $session,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $session = ExamSession::findOrFail($id);

        if ($session->filled_count > 0) {
            return response()->json([
                'message' => 'Cannot delete session with assigned applicants',
            ], 422);
        }

        $session->delete();

        return response()->json([
            'message' => 'Exam session deleted successfully',
        ]);
    }

    public function assignApplicants(Request $request, int $id): JsonResponse
    {
        $session = ExamSession::findOrFail($id);

        $validated = $request->validate([
            'applicant_ids' => 'required|array',
            'applicant_ids.*' => 'exists:applicants,id',
        ]);

        $assignedCount = 0;
        $errors = [];

        foreach ($validated['applicant_ids'] as $applicantId) {
            // Check if session is full
            if ($session->isFull()) {
                $errors[] = "Session is full";
                break;
            }

            // Check if applicant already has an assignment
            $existingAssignment = ExamAssignment::where('applicant_id', $applicantId)->first();
            if ($existingAssignment) {
                $errors[] = "Applicant ID {$applicantId} already has an exam assignment";
                continue;
            }

            // Create assignment
            ExamAssignment::create([
                'applicant_id' => $applicantId,
                'exam_session_id' => $session->id,
                'status' => 'assigned',
            ]);

            $session->incrementFilledCount();
            $assignedCount++;
        }

        return response()->json([
            'message' => "{$assignedCount} applicant(s) assigned successfully",
            'assigned_count' => $assignedCount,
            'errors' => $errors,
        ]);
    }

    public function autoAssign(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_ids' => 'required|array',
            'session_ids.*' => 'exists:exam_sessions,id',
        ]);

        // Get validated applicants who don't have exam assignments
        $applicants = Applicant::where('is_validated', true)
            ->whereDoesntHave('examAssignment')
            ->orderBy('validation_timestamp')
            ->get();

        $sessions = ExamSession::whereIn('id', $validated['session_ids'])
            ->where('status', 'scheduled')
            ->get();

        $totalAssigned = 0;

        foreach ($applicants as $applicant) {
            // Find a session with available capacity
            $session = $sessions->first(fn($s) => !$s->isFull());

            if (!$session) {
                break; // No more available sessions
            }

            ExamAssignment::create([
                'applicant_id' => $applicant->id,
                'exam_session_id' => $session->id,
                'status' => 'assigned',
            ]);

            $session->incrementFilledCount();
            $totalAssigned++;
        }

        return response()->json([
            'message' => "{$totalAssigned} applicant(s) auto-assigned successfully",
            'total_assigned' => $totalAssigned,
        ]);
    }
}
