<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentResult;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;



class AssessmentController extends Controller
{

  public function index(Request $request)
    {
        $query = Assessment::query()->where('teacher_id', Auth::id());

        // Search filter
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('instructions', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }

        // Type filter
        if ($request->has('type') && !empty($request->type)) {
            $query->where('type', $request->type);
        }

        // Pagination
        $perPage = 12;
        $assessments = $query->paginate($perPage);

        // Transform the data to match frontend expectations
        $transformed = $assessments->getCollection()->map(function ($assessment) {
            return [
                'id' => $assessment->id,
                'title' => $assessment->title,
                'status' => $assessment->status,
                'type' => $assessment->type,
                'questions_count' => $this->countQuestions($assessment),
                'points' => $assessment->total_points,
                'max_points' => $assessment->max_points,
                'created_at' => $assessment->created_at->toISOString(),
                'updated_at' => $assessment->updated_at->toISOString(),
            ];
        });

        return response()->json([
            'data' => $transformed,
            'meta' => [
                'current_page' => $assessments->currentPage(),
                'last_page' => $assessments->lastPage(),
                'total' => $assessments->total(),
            ]
        ]);
    }

    private function countQuestions($assessment)
    {
        // Assuming questions are stored as JSON in a column
        return count(json_decode($assessment->questions, true));
        
        // If using a separate questions column for count
        // return $assessment->questions_count;
    }


public function store(Request $request)
{
    $validated = $request->validate([
        'title' => 'required|string|max:255',
        'type' => 'required|in:multiple_choice,true_false,short_answer,essay,matching,fill_blank,4pics1word,comparison_table,mind_mapping',
        'total_points' => 'required|integer|min:1',
        'max_points' => 'required|integer|min:1',
        'time_limit' => 'nullable|integer|min:1',
        'instructions' => 'nullable|string',
        'questions' => 'required|array',
        'status' => 'sometimes|in:draft,published'
    ]);

    $processedQuestions = [];

    foreach ($request->questions as $qIndex => $questionData) {
        $question = [];

        // Common fields
        $question['correctAnswer'] = $questionData['correctAnswer'] ?? null;
        $question['instructions'] = $questionData['instructions'] ?? null;
        $question['hint'] = $questionData['hint'] ?? null;

        // Type-specific processing
        switch ($validated['type']) {
            case 'multiple_choice':
                $question['text'] = $questionData['text'] ?? null;
                $question['options'] = $questionData['options'] ?? [];
                break;

            case 'true_false':
                $question['correctAnswer'] = $questionData['correctAnswer'] ?? null;
                $question['text'] = $questionData['text'] ?? null;
                break;

            case 'essay':
                $question['correctAnswer'] = $questionData['sampleAnswer'] ?? null;
                $question['text'] = $questionData['text'] ?? null;
                $question['rubric'] = $questionData['rubric'] ?? null;
                break;


            case 'short_answer':
                $question['correctAnswer'] = $questionData['correctAnswer'] ?? null;
                $question['text'] = $questionData['text'] ?? null;
                break;


            case 'matching':
                $question['items'] = $questionData['items'] ?? [];
                $question['matches'] = $questionData['matches'] ?? [];
                break;

            case '4pics1word':
                $question['images'] = [];
                if ($request->hasFile("questions.{$qIndex}.images")) {
                    foreach ($request->file("questions.{$qIndex}.images") as $image) {
                        if ($image->isValid()) {
                            $path = $image->store('assessments/images', 'public');
                            $question['images'][] = $path;
                        }
                    }
                }
                break;

            case 'mind_mapping':
                $question['centralTopic'] = $questionData['centralTopic'] ?? null;
                $question['branches'] = $questionData['branches'] ?? [];
                break;

            case 'comparison_table':
                $question['headers'] = $questionData['headers'] ?? [];
                $question['rowLabels'] = $questionData['rowLabels'] ?? [];
                $question['correctAnswers'] = $questionData['correctAnswers'] ?? [];
                break;
        }

        $processedQuestions[] = $question;
    }

    $assessment = Assessment::create([
        'teacher_id' => Auth::id(),
        ...$validated,
        'questions' => json_encode($processedQuestions)
    ]);

    return response()->json([
        'assessment' => $assessment,
        'message' => 'Assessment created successfully'
    ], 201);
}


   public function show($id)
{
    $assessment = Assessment::findOrFail($id);

    return response()->json([
        'data' => [
            'id' => $assessment->id,
            'title' => $assessment->title,
            'type' => $assessment->type,
            'status' => $assessment->status,
            'instructions' => $assessment->instructions,
            'points' => $assessment->total_points,
            'max_points' => $assessment->max_points,
            'questions' => json_decode($assessment->questions, true),
            'questions_count' => $this->countQuestions($assessment),
            'created_at' => $assessment->created_at->toISOString(),
            'updated_at' => $assessment->updated_at->toISOString()
        ]
    ]);
}

    public function update(Request $request, Assessment $assessment)
    {
        $this->authorize('update', $assessment);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:multiple_choice,true_false,short_answer,essay,matching,fill_blank,4pics1word,comparison_table,mind_mapping',
            'total_points' => 'sometimes|integer|min:1',
            'max_points' => 'sometimes|integer|min:1',
            'time_limit' => 'nullable|integer|min:1',
            'instructions' => 'nullable|string',
            'questions' => 'sometimes|array',
            'status' => 'sometimes|in:draft,published'
        ]);

        $assessment->update($validated);

        return response()->json($assessment);
    }

    public function assessmentResults(){
        $studentId = Auth::id();
        return AssessmentResult::with('assessment')->where('student_id', $studentId)->get();
    }

    public function destroy(Assessment $assessment)
    {
        $this->authorize('delete', $assessment);
        
        $assessment->delete();
        
        return response()->json(null, 204);
    }
}