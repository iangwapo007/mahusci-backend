<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AssessmentResult;
use App\Models\Sequence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AssessmentResultController extends Controller
{
    /**
     * Get student results for a specific assessment in a sequence
     */
    public function getResults($sequenceId, $assessmentId)
    {
        // Verify the sequence belongs to the teacher
        $sequence = Sequence::where('id', $sequenceId)
            ->where('teacher_id', Auth::id())
            ->firstOrFail();

        // Get results with student info
        $results = AssessmentResult::with(['student', 'assessment'])
            ->where('sequence_id', $sequenceId)
            ->where('assessment_id', $assessmentId)
            ->get();

        return response()->json([
            'data' => $results->map(function ($result) {
                return [
                    'id' => $result->id,
                    'score' => $result->score,
                    'total_points' => $result->total_points,
                    'percentage' => $result->percentage,
                    'student' => [
                        'id' => $result->student->id,
                        'name' => $result->student->name,
                        'email' => $result->student->email,
                    ],
                    'assessment' => [
                        'id' => $result->assessment->id,
                        'title' => $result->assessment->title,
                        'type' => $result->assessment->type,
                    ],
                    'answers' => $result->answers,
                ];
            })
        ]);
    }

    /**
     * Update multiple assessment scores
     */
    public function updateScores(Request $request)
    {
        $request->validate([
            'scores' => 'required|array',
            'scores.*' => 'numeric|min:0',
        ]);

        $updatedCount = 0;

        foreach ($request->scores as $resultId => $score) {
            $result = AssessmentResult::find($resultId);
            
            // Verify the result belongs to a sequence owned by the teacher
            if ($result && $result->sequence->teacher_id == Auth::id()) {
                $result->score = $score;
                $result->percentage = ($score / $result->total_points) * 100;
                $result->save();
                $updatedCount++;
            }
        }

        return response()->json([
            'message' => "$updatedCount scores updated successfully",
            'updated' => $updatedCount,
        ]);
    }

    /**
     * Get assessments for a sequence (belonging to the teacher)
     */
    public function getSequenceAssessments($sequenceId)
    {
        $sequence = Sequence::where('id', $sequenceId)
            ->where('teacher_id', Auth::id())
            ->with(['items.assessment'])
            ->firstOrFail();

        $assessments = $sequence->items
            ->filter(fn($item) => $item->item_type === 'assessment')
            ->map(fn($item) => $item->assessment)
            ->filter()
            ->unique('id')
            ->values();

        return response()->json([
            'data' => $assessments->map(function ($assessment) {
                return [
                    'id' => $assessment->id,
                    'title' => $assessment->title,
                    'type' => $assessment->type,
                ];
            })
        ]);
    }
}