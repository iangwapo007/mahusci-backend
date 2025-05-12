<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LessonController;
use App\Http\Controllers\Api\AssessmentController;
use App\Http\Controllers\Api\AssessmentResultController;
use App\Http\Controllers\Api\CurriculumSequenceController;
use App\Http\Controllers\Api\LearningHistoryController;
use App\Http\Controllers\Api\SequenceController;
use App\Http\Controllers\Api\StudentSequenceController;
use App\Http\Controllers\Api\TeacherController;
use App\Models\Assessment;
use App\Models\LearningHistory;
use App\Models\Lesson;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public APIs
Route::post('/login', [AuthController::class, 'login'])->name('user.login');
Route::post('/register', [UserController::class,'store'])->name('user.store');
Route::post('/teachers/register', [TeacherController::class, 'store']); 

// Private APIs
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/logout', [AuthController::class, 'logout']);
    Route::get('/teachers/{id}', [TeacherController::class, 'show']);
    Route::put('/teachers/{id}', [TeacherController::class, 'update']); 
    Route::delete('/teachers/{id}', [TeacherController::class, 'destroy']); 
    Route::put('/teachers/profile-picture', [TeacherController::class, 'updateProfilePicture']); 
    
    Route::controller(UserController::class)->group(function () {
        Route::get('/user',                         'index');
        Route::get('/user/profile',                 'profile');
        Route::get('/user/{id}',                    'show');
        Route::put('/user/update/{id}',             'update')->name('user.update');
        Route::put('/user/profile-picture',         'updateProfilePicture');
        Route::delete('/user/{id}',                 'destroy');
    });
    
    // Additional routes for drafts
    Route::get('lessons/drafts', [LessonController::class, 'drafts']);
    Route::post('lessons/{lesson}/publish', [LessonController::class, 'publish']);
    

    // Assessments routes (nested under lessons)
    Route::put('/assessments/{assessment}', [AssessmentController::class, 'update']);
    Route::get('/assessments/results', [AssessmentController::class, 'assessmentResults']);
    Route::delete('/assessments/{assessment}', [AssessmentController::class, 'destroy']);

    Route::post('/lessons/assessments', [AssessmentController::class, 'store']);
    Route::get('/lessons/assessments', [AssessmentController::class, 'index']);
    Route::get('/lessons/assessments/{assessment}', [AssessmentController::class, 'show']);

    // Sequence Builder Routes
    Route::apiResource('sequences', SequenceController::class);
    
    // Get published lessons and assessments for sequence builder
    Route::get('sequence-builder/lessons', function () {
        $lessons = Lesson::where('is_draft', 'published')
            ->where('teacher_id', Auth::id())
            ->select('id', 'title', 'subject', 'quarter', 'updated_at')
            ->get();
            
        return response()->json($lessons);
    });
    
    Route::get('sequence-builder/assessments', function () {
        $assessments = Assessment::where('status', 'published')
            ->where('teacher_id', Auth::id()) 
            ->select('id', 'title', 'type', 'updated_at')
            ->get();
            
        return response()->json($assessments);
    });

    Route::apiResource('lessons', LessonController::class);
    Route::get('/student/lessons', [LessonController::class, 'forStudentIndex']);
    Route::get('/student/curriculum-sequences', [CurriculumSequenceController::class, 'forStudentIndex']);

    Route::get('/load-sequences/{sequence}', [StudentSequenceController::class, 'getSequence']);
    Route::post('/sequence-items/{item}/start', [StudentSequenceController::class, 'startItem']);
    Route::post('/sequence-items/{item}/complete-lesson', [StudentSequenceController::class, 'completeLesson']);
    Route::post('/sequence-items/{item}/submit-assessment', [StudentSequenceController::class, 'submitAssessment']);
    Route::get('/sequences/{sequence}/progress', [StudentSequenceController::class, 'getProgress']);
    Route::get('/sequences/{sequence}/{sequenceItem}/progress', [StudentSequenceController::class, 'getSpecificProgress']);

    // Curriculum Sequences
    Route::apiResource('curriculum-sequences', CurriculumSequenceController::class);
    Route::post('curriculum-sequences/quarter/{sequence}', [CurriculumSequenceController::class, 'storeQuarterSequence']);

    Route::get('/student/curriculum-progress', [StudentSequenceController::class, 'indexWithProgress']);
 
    Route::get('/student/learning-history', function() {
        return LearningHistory::where('student_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    });    Route::post('/student/learning-history', [LearningHistoryController::class, 'store']);

    Route::get('/sequences/{sequenceId}/assessments/{assessmentId}/results', [AssessmentResultController::class, 'getResults']);
    Route::post('/assessment-results/update-scores', [AssessmentResultController::class, 'updateScores']);
    Route::get('/sequences/{sequenceId}/assessments', [AssessmentResultController::class, 'getSequenceAssessments']);
});





