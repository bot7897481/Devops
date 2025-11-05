<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamQuestion;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class QuestionBankController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $questions = ExamQuestion::query()
            ->when($request->section, fn($q, $section) => $q->where('section', $section))
            ->when($request->difficulty, fn($q, $diff) => $q->where('difficulty_level', $diff))
            ->when($request->search, function($q, $search) {
                $q->where('question_text', 'like', "%{$search}%");
            })
            ->orderBy('section')
            ->orderBy('difficulty_level')
            ->paginate(50);

        return response()->json($questions);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'section' => 'required|in:reading,math_computation,applied_math,language,aptitude',
            'question_text' => 'required|string',
            'question_image' => 'nullable|image|max:2048',
            'option_a' => 'required|string',
            'option_b' => 'required|string',
            'option_c' => 'required|string',
            'option_d' => 'required|string',
            'correct_answer' => 'required|in:a,b,c,d',
            'difficulty_level' => 'required|in:easy,medium,hard',
            'tags' => 'nullable|array',
        ]);

        if ($request->hasFile('question_image')) {
            $path = $request->file('question_image')->store('exam_questions', 'private');
            $validated['question_image_path'] = $path;
        }

        $question = ExamQuestion::create($validated);

        return response()->json([
            'message' => 'Question created successfully',
            'data' => $question,
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $question = ExamQuestion::findOrFail($id);
        return response()->json(['data' => $question]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $question = ExamQuestion::findOrFail($id);

        $validated = $request->validate([
            'section' => 'sometimes|in:reading,math_computation,applied_math,language,aptitude',
            'question_text' => 'sometimes|string',
            'question_image' => 'nullable|image|max:2048',
            'option_a' => 'sometimes|string',
            'option_b' => 'sometimes|string',
            'option_c' => 'sometimes|string',
            'option_d' => 'sometimes|string',
            'correct_answer' => 'sometimes|in:a,b,c,d',
            'difficulty_level' => 'sometimes|in:easy,medium,hard',
            'tags' => 'nullable|array',
        ]);

        if ($request->hasFile('question_image')) {
            // Delete old image if exists
            if ($question->question_image_path) {
                Storage::disk('private')->delete($question->question_image_path);
            }
            $path = $request->file('question_image')->store('exam_questions', 'private');
            $validated['question_image_path'] = $path;
        }

        $question->update($validated);

        return response()->json([
            'message' => 'Question updated successfully',
            'data' => $question,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $question = ExamQuestion::findOrFail($id);

        // Check if question has been used
        if ($question->responses()->exists()) {
            return response()->json([
                'message' => 'Cannot delete question that has been used in exams',
            ], 422);
        }

        // Delete image if exists
        if ($question->question_image_path) {
            Storage::disk('private')->delete($question->question_image_path);
        }

        $question->delete();

        return response()->json([
            'message' => 'Question deleted successfully',
        ]);
    }

    public function importQuestions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:csv,xlsx',
            'section' => 'required|in:reading,math_computation,applied_math,language,aptitude',
        ]);

        // TODO: Implement CSV/Excel import logic using Maatwebsite Excel
        // For now, return placeholder response

        return response()->json([
            'message' => 'Question import feature coming soon',
        ]);
    }

    public function statistics(): JsonResponse
    {
        $stats = [
            'total_questions' => ExamQuestion::count(),
            'by_section' => ExamQuestion::selectRaw('section, count(*) as count')
                ->groupBy('section')
                ->pluck('count', 'section'),
            'by_difficulty' => ExamQuestion::selectRaw('difficulty_level, count(*) as count')
                ->groupBy('difficulty_level')
                ->pluck('count', 'difficulty_level'),
        ];

        return response()->json(['data' => $stats]);
    }
}
