<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sequence;
use App\Models\SequenceItem;
use App\Models\StudentProgress;
use App\Models\Assessment;
use App\Models\AssessmentResult;
use App\Models\CurriculumSequence;
use App\Models\LearningHistory;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class StudentSequenceController extends Controller
{
    public function getSequence($sequenceId)
    {
        $studentId = Auth::id(); 
        
        $sequence = Sequence::with(['items' => function($query) {
            $query->orderBy('position');
        }])->findOrFail($sequenceId);

        $items = $sequence->items->map(function($item) use ($studentId) {
            $progress = StudentProgress::firstOrCreate([
                'student_id' => $studentId,
                'sequence_id' => $item->sequence_id,
                'sequence_item_id' => $item->id
            ]);
            
            $itemData = $item->toArray();
            $itemData['progress'] = $progress;
            $itemData['is_locked'] = $this->isItemLocked($item, $studentId);
            
            return $itemData;
        });

        return response()->json([
            'sequence' => $sequence,
            'items' => $items
        ]);
    }

    private function isItemLocked($item, $studentId)
    {
        if ($item->position === 0) return false;
        
        $previousItem = SequenceItem::where('sequence_id', $item->sequence_id)
            ->where('position', $item->position - 1)
            ->first();
            
        if (!$previousItem) return false;
        
        $previousProgress = StudentProgress::where('student_id', $studentId)
            ->where('sequence_item_id', $previousItem->id)
            ->first();
            
        return !($previousProgress && $previousProgress->status === 'completed');
    }

    public function startItem(Request $request, $itemId)
    {
        $progress = StudentProgress::where([
            'student_id' => Auth::id(), 
            'sequence_item_id' => $itemId
        ])->firstOrFail();

        if ($progress->status === 'not_started') {

            $item = SequenceItem::findOrFail($progress->sequence_item_id);

            $subject = '';

            if ($item->item_type === "lesson") {
                $lesson = Lesson::findOrFail($item->item_id);
                $subject = $lesson->subject;
            } elseif ($item->item_type === "assessment") {
                $assessment = Assessment::findOrFail($item->item_id);
                $subject = $assessment->type; 
            }

            $sequence = Sequence::findOrFail($progress->sequence_id);

               // Record the learning history
                LearningHistory::create([
                 'student_id' => Auth::id(),
                 'type' => 'Assessment',
                 'details' => 'Attempted "' . $sequence->title . '" ' . $item->item_type,
                 'subject' => $subject,            
            ]);
            $progress->update([
                'status' => 'in_progress',
                'started_at' => now()
            ]);
        }

        $item = SequenceItem::with(['assessment', 'lesson'])->findOrFail($itemId);
        
        return response()->json([
            'item' => $item,
            'progress' => $progress
        ]);
    }

    public function completeLesson(Request $request, $itemId)
{
    $studentId = Auth::id();

    // Get student progress
    $progress = StudentProgress::where([
        'student_id' => $studentId,
        'sequence_item_id' => $itemId
    ])->firstOrFail();

    // Ensure the lesson and sequence exist
    $item = SequenceItem::findOrFail($progress->sequence_item_id);
    $lesson = Lesson::findOrFail($item->item_id);
    $sequence = Sequence::findOrFail($progress->sequence_id);

    Log::info(["item", $item, "lesson", $lesson, "sequence:", $sequence]);
    

    // Record the learning history
    LearningHistory::create([
        'student_id' => $studentId,
        'type' => 'Lesson',
        'details' => 'Completed "' . $sequence->title . '" lesson',
        'subject' => $lesson->subject,
    ]);

    // Update progress
    $progress->update([
        'status' => 'completed',
        'completed_at' => now(),
    ]);

    return response()->json($progress);
}

  

private function calculateScore($assessment, $answers)
{
    $score = 0;

    // Decode JSON if needed
    $questions = is_string($assessment->questions)
        ? json_decode($assessment->questions, true)
        : $assessment->questions;

    $totalQuestions = count($questions);
    $scorePerQuestion = $totalQuestions > 0 ? $assessment->total_points / $totalQuestions : 0;

    Log::info('Item value: ', ['assessment' => $questions, 'answers' => $answers]);

    foreach ($questions as $index => $question) {
        switch ($assessment->type) {
            case 'multiple_choice':
            case 'true_false':
            case '4pics1word':
                if (strtolower(isset($answers[$index])) && strtolower($answers[$index]) === strtolower($question['correctAnswer'])) {
                    $score += $scorePerQuestion;
                }
                break;

                case 'short_answer':
                    $prompt = "Score this short answer (0-{$scorePerQuestion} points):\n";
                    $prompt .= "QUESTION: {$question['text']}\n";
                    $prompt .= "EXPECTED: {$question['correctAnswer']}\n";
                    $prompt .= "ANSWER: {$answers[$index]}\n";
                    $prompt .= "SCORE (0-{$scorePerQuestion}, must be numeric):";

                    Log::debug("Gemini Short Answer Prompt", ['prompt' => $prompt]);
                    $response = $this->callGeminiAPI($prompt);
                    Log::debug("Gemini Short Answer Response", ['response' => $response]);
                    
                    $score += $this->parseGeminiScore($response, $scorePerQuestion);
                    break;

                case 'essay':
                    $prompt = "Score this essay (0-{$scorePerQuestion} points):\n";
                    $prompt .= "QUESTION: {$question['text']}\n";
                    $prompt .= "ANSWER: {$answers[$index]}\n";
                    $prompt .= "SCORE (0-{$scorePerQuestion}, must be numeric):";

                    Log::debug("Gemini Essay Prompt", ['prompt' => $prompt]);
                    $response = $this->callGeminiAPI($prompt);
                    Log::debug("Gemini Essay Response", ['response' => $response]);
                    
                    $score += $this->parseGeminiScore($response, $scorePerQuestion);
                    break;


            case 'matching':
                if (isset($answers[$index])) {
                    $correctMatches = 0;
                    $totalMatches = count($question['matches']);

                    foreach ($question['matches'] as $match) {
                        if (isset($answers[$index][$match['item']]) &&
                            $answers[$index][$match['item']] === $match['match']) {
                            $correctMatches++;
                        }
                    }

                    if ($totalMatches > 0) {
                        $score += ($correctMatches / $totalMatches) * $scorePerQuestion;
                    }
                }
                break;

            case 'fill_blank':
                if (isset($answers[$index])) {
                    $correctBlanks = 0;
                    $totalBlanks = count($question['blanks']);

                    foreach ($question['blanks'] as $blank) {
                        if (isset($answers[$index][$blank['id']]) &&
                            strtolower(trim($answers[$index][$blank['id']])) === strtolower(trim($blank['correctAnswer']))) {
                            $correctBlanks++;
                        }
                    }

                    if ($totalBlanks > 0) {
                        $score += ($correctBlanks / $totalBlanks) * $scorePerQuestion;
                    }
                }
                break;

                case 'comparison_table':
                    if (isset($answers[$index])) {
                        $correctCells = 0;
                        $totalCells = 0;
                
                        Log::debug('Starting comparison table evaluation', [
                            'rowLabels' => $question['rowLabels'],
                            'headers' => $question['headers'],
                            'studentAnswers' => $answers[$index],
                            'correctAnswers' => $question['correctAnswers']
                        ]);
                
                        foreach ($question['rowLabels'] as $rowIndex => $rowLabel) {
                            if (!isset($answers[$index][$rowLabel])) {
                                Log::debug("Missing student answer for row: $rowLabel");
                                continue;
                            }
                            
                            if (!isset($question['correctAnswers'][$rowIndex])) {
                                Log::debug("Missing correct answer for row index: $rowIndex ($rowLabel)");
                                continue;
                            }
                
                            // Get the correct answer object for this row
                            $correctRow = $question['correctAnswers'][$rowIndex];
                            
                            // Convert correct answer object to array with consistent keys
                            $correctAnswersArray = (array)$correctRow;
                            
                            foreach ($question['headers'] as $colIndex => $header) {
                                $totalCells++;
                                
                                // Get student answer
                                $studentAnswer = isset($answers[$index][$rowLabel][$header]) 
                                    ? strtolower(trim($answers[$index][$rowLabel][$header]))
                                    : null;
                                
                                // Get correct answer by index (assuming same order as headers)
                                $correctValue = isset(array_values($correctAnswersArray)[$colIndex]) 
                                    ? strtolower(trim(array_values($correctAnswersArray)[$colIndex]))
                                    : null;
                
                                Log::debug("Comparing answers", [
                                    'row' => $rowLabel,
                                    'header' => $header,
                                    'colIndex' => $colIndex,
                                    'studentAnswer' => $studentAnswer,
                                    'correctValue' => $correctValue,
                                    'match' => $studentAnswer === $correctValue
                                ]);
                
                                if ($studentAnswer !== null && $studentAnswer === $correctValue) {
                                    $correctCells++;
                                    Log::debug("Correct match for $rowLabel - $header");
                                }
                            }
                        }
                
                        Log::debug("Comparison table results", [
                            'correctCells' => $correctCells,
                            'totalCells' => $totalCells,
                            'score' => $totalCells > 0 ? ($correctCells / $totalCells) * $scorePerQuestion : 0
                        ]);
                
                        if ($totalCells > 0) {
                            $score += ($correctCells / $totalCells) * $scorePerQuestion;
                        }
                    }
                    break;

            case 'mind_mapping':
                if (isset($answers[$index])) {
                    $correctBranches = 0;
                    $totalBranches = 0;

                    foreach ($question['branches'] as $branchIndex => $branch) {
                        $branchScore = 0;
                        $branchTotal = 0;

                        $correctSubBranches = explode(',', $branch['subBranches']);

                        foreach ($correctSubBranches as $correctSubBranch) {
                            $correctSubBranch = strtolower(trim($correctSubBranch));
                            if (!empty($correctSubBranch)) {
                                $branchTotal++;
                                $subFound = false;

                                if (isset($answers[$index]['branches'][$branchIndex])) {
                                    $userSubBranches = explode(',', $answers[$index]['branches'][$branchIndex]['subBranches']);
                                    foreach ($userSubBranches as $userSubBranch) {
                                        if (strtolower(trim($userSubBranch)) === $correctSubBranch) {
                                            $subFound = true;
                                            break;
                                        }
                                    }
                                }

                                if ($subFound) {
                                    $branchScore++;
                                }
                            }
                        }

                        $correctBranches += $branchScore;
                        $totalBranches += $branchTotal;
                    }

                    if ($totalBranches > 0) {
                        $score += ($correctBranches / $totalBranches) * $scorePerQuestion;
                    }
                }
                break;
        }
    }

    return min($score, $assessment->total_points);
}

private function callGeminiAPI($prompt)
{
    $apiKey = 'AIzaSyC4oFXxLH1F74WmLYWfWRwqljI_7KASZYY'; // Replace with your actual key
    $model = 'gemini-2.0-flash'; // Confirmed working model
    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

    $data = [
        'contents' => [
            'parts' => [
                ['text' => $prompt]
            ]
        ],
        'generationConfig' => [
            'maxOutputTokens' => 10, // Force short numeric responses
            'temperature' => 0.0,   // Disable creativity for scoring
        ]
    ];

    try {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // Debug logging
        Log::debug("Gemini API Request", [
            'model' => $model,
            'prompt_truncated' => substr($prompt, 0, 100) . '...',
            'config' => $data['generationConfig']
        ]);

        if ($curlError) {
            throw new \Exception("CURL Error: " . $curlError);
        }

        if ($httpCode !== 200) {
            $errorDetails = json_decode($response, true)['error'] ?? [];
            throw new \Exception("API Error {$httpCode}: " . ($errorDetails['message'] ?? 'Unknown error'));
        }

        Log::info("Gemini API Success", [
            'model' => $model,
            'response_truncated' => substr($response, 0, 200) . '...'
        ]);

        return $response;

    } catch (\Exception $e) {
        Log::error("Gemini API Failure", [
            'error' => $e->getMessage(),
            'model' => $model,
            'trace' => $e->getTraceAsString()
        ]);
        throw $e;
    }
}

private function parseGeminiScore($response, $maxScore)
{
    try {
        $data = json_decode($response, true);
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

        // Extract first numeric value
        preg_match('/\d+/', $text, $matches);
        $score = $matches[0] ?? 0;

        // Clamp score to allowed range
        return min(max((int)$score, 0), $maxScore);

    } catch (\Exception $e) {
        Log::error("Score Parsing Failed", [
            'response_sample' => substr($response, 0, 200),
            'error' => $e->getMessage()
        ]);
        return 0; // Fail-safe return
    }
}


      public function submitAssessment(Request $request, $itemId)
    {
        $item = SequenceItem::with(['assessment'])->findOrFail($itemId);
        $assessment = $item->assessment;
        
        $validated = $request->validate([
            'answers' => 'required|array',
        ]);
        
        // Calculate score based on assessment type and correct answers
        $score = $this->calculateScore($assessment, $validated['answers']);
        $percentage = ($score / $assessment->total_points) * 100;
        $passingpercentage = ($assessment->max_points / $assessment->total_points) * 100;

        Log::info(' value: ', ['sp' => $percentage, 'pp' => $passingpercentage, $assessment->max_points, $assessment->total_points]);

        if($percentage < $passingpercentage){
            // Update progress
            $progress = StudentProgress::where([
                'student_id' => Auth::id(), //
                'sequence_item_id' => $itemId
            ])->firstOrFail();

            $progress->update([
                'status' => 'in_progress',
                'score' => $score,
                'answers' => null,
                'completed_at' => null,
                'attempts' => $progress->attempts + 1
            ]);
            
            $passing_status = 'failed';

            // Ensure the Assessment and sequence exist
            $item = SequenceItem::findOrFail($itemId);
            $lesson = Assessment::findOrFail($item->item_id);
            $sequence = Sequence::findOrFail($progress->sequence_id);

            // Record the learning history
            LearningHistory::create([
                'student_id' => Auth::id(),
                'type' => 'Assessment',
                'details' => 'Failed "' . $sequence->title . '" assessment with the score of ' . $score,
                'subject' => $lesson->type,
            ]);
        }else{
            // Save assessment result
            $result = AssessmentResult::create([
                'student_id' => Auth::id(), //
                'assessment_id' => $assessment->id,
                'sequence_id' => $item->sequence_id,
                'score' => $score,
                'total_points' => $assessment->total_points,
                'percentage' => $percentage,
                'answers' => $validated['answers']
            ]);

            // Update progress
            $progress = StudentProgress::where([
                'student_id' => Auth::id(), //
                'sequence_item_id' => $itemId
            ])->firstOrFail();

             // Ensure the Assessment and sequence exist
             $item = SequenceItem::findOrFail($itemId);
             $lesson = Assessment::findOrFail($item->item_id);
             $sequence = Sequence::findOrFail($progress->sequence_id); 
 
             // Record the learning history
             LearningHistory::create([
                 'student_id' => Auth::id(),
                 'type' => 'Assessment',
                 'details' => 'Passed "' . $sequence->title . '" assessment with the score of ' . $score,
                 'subject' => $lesson->type,
             ]);

            $progress->update([
                'status' => 'completed',
                'score' => $score,
                'answers' => $validated['answers'],
                'completed_at' => now(),
                'attempts' => $progress->attempts + 1
            ]);
            
            $passing_status = 'passed';
        }
        
        return response()->json([
            'result' => $result ?? [],
            'progress' => $progress,
            'passing_status' => $passing_status,
            'percentage' => $percentage,
            'answers' => $validated['answers'],
        ]);
    }

    public function indexWithProgress(Request $request)
    {
        $studentId = Auth::id(); // Assuming student is authenticated
        
        $curricula = CurriculumSequence::with([
            'quarters.items.sequence.items' => function($query) {
                $query->orderBy('position');
            }
        ])
        ->get()
        ->map(function ($curriculum) use ($studentId) {
            $totalItems = 0;
            $completedItems = 0;
            $inProgressItems = 0;
            
            $quarters = $curriculum->quarters->map(function ($quarter) use ($studentId, &$totalItems, &$completedItems, &$inProgressItems) {
                $sequences = $quarter->items->map(function ($item) use ($studentId, &$totalItems, &$completedItems, &$inProgressItems) {
                    $sequence = $item->sequence;
                    
                    // Count ALL items in this sequence using your specified method
                    $sequenceItems = SequenceItem::where('sequence_id', $sequence->id)
                        ->orderBy('position')
                        ->get();
                    
                    $totalItems += $sequenceItems->count();
                    
                    // Get progress for each item
                    $itemsWithProgress = $sequenceItems->map(function ($seqItem) use ($studentId, $sequence) {
                        $progress = StudentProgress::where([
                            'student_id' => $studentId,
                            'sequence_id' => $sequence->id,
                            'sequence_item_id' => $seqItem->id
                        ])->first();
                        
                        return [
                            'id' => $seqItem->id,
                            'item_id' => $seqItem->item_id,
                            'item_type' => $seqItem->item_type,
                            'position' => $seqItem->position,
                            'title' => $seqItem->title,
                            'status' => $progress ? $progress->status : 'not_started',
                            'score' => $progress ? $progress->score : null,
                            'attempts' => $progress ? $progress->attempts : 0,
                            'started_at' => $progress ? $progress->started_at : null,
                            'completed_at' => $progress ? $progress->completed_at : null
                        ];
                    });
                    
                    // Count completed and in-progress items
                    $completedItems += $itemsWithProgress->where('status', 'completed')->count();
                    $inProgressItems += $itemsWithProgress->where('status', 'in_progress')->count();
                    
                    return [
                        'sequence_id' => $sequence->id,
                        'sequence_title' => $sequence->title,
                        'sequence_type' => $sequenceItems->first()?->item_type ?? 'lesson',
                        'items' => $itemsWithProgress,
                        'progress_percentage' => $sequenceItems->count() > 0 
                            ? round(($itemsWithProgress->where('status', 'completed')->count() / $sequenceItems->count()) * 100)
                            : 0
                    ];
                });
                
                return [
                    'quarter' => $quarter->quarter,
                    'sequences' => $sequences
                ];
            });
            
            // Calculate overall curriculum progress
            $progressPercentage = $totalItems > 0 
                ? round(($completedItems / $totalItems) * 100)
                : 0;
            
            return [
                'id' => $curriculum->id,
                'title' => $curriculum->title,
                'description' => $curriculum->description,
                'curriculum_image' => $curriculum->curriculum_image,
                'grades' => $curriculum->grades,
                'is_draft' => $curriculum->is_draft,
                'created_at' => $curriculum->created_at,
                'progress' => [
                    'percentage' => $progressPercentage,
                    'completed_items' => $completedItems,
                    'in_progress_items' => $inProgressItems,
                    'total_items' => $totalItems
                ],
                'quarters' => $quarters
            ];
        });
    
        return response()->json($curricula);
    }

    public function getSpecificProgress($sequenceId, $sequenceItemId)
    {
        $progress = StudentProgress::with('sequenceItem')
            ->where('student_id', Auth::id()) //
            ->where('sequence_id', $sequenceId)
            ->where('sequence_item_id', $sequenceItemId)
            ->get();
            
        return response()->json($progress);
    }

       public function getProgress($sequenceId)
    {
        $progress = StudentProgress::with('sequenceItem')
            ->where('student_id', Auth::id())
            ->where('sequence_id', $sequenceId)
            ->orderBy('sequence_item_id')
            ->get();
            
        return response()->json($progress);
    }
}