<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\LessonContent;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class LessonController extends Controller
{
  public function index(Request $request)
{
    // Validate query parameters
    $validated = $request->validate([
        'search' => 'sometimes|string|max:255',
        'subject' => 'sometimes|in:biology,chemistry,physics,earth_science,astronomy',
        'grade' => 'sometimes|in:7,8,9,10,11,12',
        'difficulty' => 'sometimes|in:beginner,intermediate,advanced',
        'quarter' => 'sometimes|in:1,2,3,4',
        'is_draft' => 'sometimes|boolean',
        'per_page' => 'sometimes|integer|min:1|max:100',
        'page' => 'sometimes|integer|min:1'
    ]);

    // Base query with eager loading
    $query = Lesson::with(['contents', 'media'])
        ->where('teacher_id', Auth::id())
        ->orderBy('updated_at', 'desc');

    // Apply filters
    if ($request->has('search') && !empty($validated['search'])) {
        $searchTerm = $validated['search'];
        $query->where(function($q) use ($searchTerm) {
            $q->where('title', 'like', "%{$searchTerm}%")
              ->orWhere('objectives', 'like', "%{$searchTerm}%")
              ->orWhereHas('contents', function($q) use ($searchTerm) {
                  $q->where('content', 'like', "%{$searchTerm}%");
              });
        });
    }

    if ($request->has('subject') && !empty($validated['subject'])) {
        $query->where('subject', $validated['subject']);
    }

    if ($request->has('grade') && !empty($validated['grade'])) {
        $query->where('grades', 'like', "%{$validated['grade']}%");
    }

    if ($request->has('difficulty') && !empty($validated['difficulty'])) {
        $query->where('difficulty', $validated['difficulty']);
    }

    if ($request->has('quarter') && !empty($validated['quarter'])) {
        $query->where('quarter', $validated['quarter']);
    }

    if ($request->has('is_draft')) {
        $query->where('is_draft', $validated['is_draft']);
    }

    // Paginate results
    $perPage = $request->input('per_page', 12); // Default to 12 items per page
    $lessons = $query->paginate($perPage);
    

    // Transform the data for the frontend
    $transformedLessons = $lessons->getCollection()->map(function ($lesson) {
        return [
            'id' => $lesson->id,
            'title' => $lesson->title,
            'subject' => $lesson->subject,
            'grades' => $lesson->grades,
            'difficulty' => $lesson->difficulty,
            'quarter' => $lesson->quarter,
            'objectives' => $lesson->objectives,
            'is_draft' => $lesson->is_draft,
            'created_at' => $lesson->created_at->toDateTimeString(),
            'updated_at' => $lesson->updated_at->toDateTimeString(),
            'contents_count' => $lesson->contents->count(),
            'contents' => $lesson->contents,
            'media' => $lesson->media,
            'media_count' => $lesson->media->count(),
            // Add any other fields you want to expose
        ];
    });

    // Return paginated response with transformed data
    return response()->json([
        'data' => $transformedLessons,
        'meta' => [
            'current_page' => $lessons->currentPage(),
            'last_page' => $lessons->lastPage(),
            'per_page' => $lessons->perPage(),
            'total' => $lessons->total(),
            'from' => $lessons->firstItem(),
            'to' => $lessons->lastItem(),
            'path' => $lessons->path(),
            'links' => $lessons->linkCollection()->toArray(),
        ]
    ]);
}

  public function forStudentIndex(Request $request)
{
    // Validate query parameters
    $validated = $request->validate([
        'search' => 'sometimes|string|max:255',
        'subject' => 'sometimes|in:biology,chemistry,physics,earth_science,astronomy',
        'grade' => 'sometimes|in:7,8,9,10,11,12',
        'difficulty' => 'sometimes|in:beginner,intermediate,advanced',
        'quarter' => 'sometimes|in:1,2,3,4',
        'is_draft' => 'sometimes|boolean',
        'per_page' => 'sometimes|integer|min:1|max:100',
        'page' => 'sometimes|integer|min:1'
    ]);

    // Base query with eager loading
    $query = Lesson::with(['contents', 'media'])
        ->orderBy('updated_at', 'desc');

    // Apply filters
    if ($request->has('search') && !empty($validated['search'])) {
        $searchTerm = $validated['search'];
        $query->where(function($q) use ($searchTerm) {
            $q->where('title', 'like', "%{$searchTerm}%")
              ->orWhere('objectives', 'like', "%{$searchTerm}%")
              ->orWhereHas('contents', function($q) use ($searchTerm) {
                  $q->where('content', 'like', "%{$searchTerm}%");
              });
        });
    }

    if ($request->has('subject') && !empty($validated['subject'])) {
        $query->where('subject', $validated['subject']);
    }

    if ($request->has('grade') && !empty($validated['grade'])) {
        $query->where('grades', 'like', "%{$validated['grade']}%");
    }

    if ($request->has('difficulty') && !empty($validated['difficulty'])) {
        $query->where('difficulty', $validated['difficulty']);
    }

    if ($request->has('quarter') && !empty($validated['quarter'])) {
        $query->where('quarter', $validated['quarter']);
    }

    if ($request->has('is_draft')) {
        $query->where('is_draft', $validated['is_draft']);
    }

    // Paginate results
    $perPage = $request->input('per_page', 12); // Default to 12 items per page
    $lessons = $query->paginate($perPage);
    

    // Transform the data for the frontend
    $transformedLessons = $lessons->getCollection()->map(function ($lesson) {
        return [
            'id' => $lesson->id,
            'title' => $lesson->title,
            'subject' => $lesson->subject,
            'grades' => $lesson->grades,
            'difficulty' => $lesson->difficulty,
            'quarter' => $lesson->quarter,
            'objectives' => $lesson->objectives,
            'is_draft' => $lesson->is_draft,
            'created_at' => $lesson->created_at->toDateTimeString(),
            'updated_at' => $lesson->updated_at->toDateTimeString(),
            'contents_count' => $lesson->contents->count(),
            'contents' => $lesson->contents,
            'media' => $lesson->media,
            'media_count' => $lesson->media->count(),
            // Add any other fields you want to expose
        ];
    });

    // Return paginated response with transformed data
    return response()->json([
        'data' => $transformedLessons,
        'meta' => [
            'current_page' => $lessons->currentPage(),
            'last_page' => $lessons->lastPage(),
            'per_page' => $lessons->perPage(),
            'total' => $lessons->total(),
            'from' => $lessons->firstItem(),
            'to' => $lessons->lastItem(),
            'path' => $lessons->path(),
            'links' => $lessons->linkCollection()->toArray(),
        ]
    ]);
}

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'subject' => 'required|string',
            'grades' => 'required|array',
            'grades.*' => 'in:7,8,9,10,11,12',
            'difficulty' => 'required|in:beginner,intermediate,advanced',
            'quarter' => 'required|in:1,2,3,4',
            'objectives' => 'required|array',
            'objectives.*' => 'string',
            'content_sections' => 'required|array',
            'content_sections.*.content' => 'nullable|string',
            'content_sections.*.image_name' => 'nullable|string',
            'content_sections.*.images' => 'sometimes|array',
            'content_sections.*.images.*' => 'file|mimes:jpeg,png,jpg,gif,mp4,mov,avi,wmv|max:204800',
            'attachments' => 'sometimes|array',
            'attachments.*' => 'sometimes|file|mimes:pdf,doc,docx,ppt,pptx,txt|max:10240',
            'is_draft' => 'sometimes|boolean',
            'teacher_id' => 'sometimes|integer'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Create the lesson
        $lesson = Lesson::create([
            'title' => $request->title,
            'subject' => $request->subject,
            'grades' => implode(',', $request->grades),
            'difficulty' => $request->difficulty,
            'quarter' => $request->quarter,
            'objectives' => $request->objectives,
            'is_draft' => $request->is_draft ?? false,
            'teacher_id' => $request->teacher_id ?? Auth::id()
        ]);

        // Add content sections
        foreach ($request->content_sections as $index => $section) {
            $content = LessonContent::create([
                'lesson_id' => $lesson->id,
                'content' => $section['content'],
                'image_name' => $section['image_name'],
                'order' => $index
            ]);

            // Handle section images
            if (isset($section['images'])) {
                foreach ($section['images'] as $image) {
                    $this->storeMedia($image, $content);
                }
            }
        }

        // Handle attachments
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $this->storeMedia($file, $lesson);
            }
        }

        return response()->json($lesson->load(['contents', 'media']), 201);
    }

    public function show($id)
    {
        $lesson = Lesson::with(['contents', 'media'])->findOrFail($id);
        return response()->json($lesson);
    }

      public function update(Request $request, $id)
    {
        $lesson = Lesson::findOrFail($id);
    
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'subject' => 'sometimes|string',
            'grades' => 'sometimes|array',
            'grades.*' => 'in:7,8,9,10,11,12',
            'difficulty' => 'sometimes|in:beginner,intermediate,advanced',
            'quarter' => 'sometimes|in:1,2,3,4',
            'objectives' => 'sometimes|array',
            'objectives.*' => 'string',
            'content_sections' => 'sometimes|array',
            'content_sections.*.content' => 'nullable|string',
            'content_sections.*.image_name' => 'nullable|string',
            'content_sections.*.images' => 'sometimes|array',
            'content_sections.*.images.*' => 'file|mimes:jpeg,png,jpg,gif,mp4,mov,avi,wmv|max:204800',
            'attachments' => 'sometimes|array',
            'attachments.*' => 'file|mimes:pdf,doc,docx,ppt,pptx,txt|max:10240',
            'is_draft' => 'sometimes|boolean'
        ]);
    
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
    
        $lesson->update([
            'title' => $request->filled('title') ? $request->title : $lesson->title,
            'subject' => $request->filled('subject') ? $request->subject : $lesson->subject,
            'grades' => $request->filled('grades') ? implode(',', $request->grades) : $lesson->grades,
            'difficulty' => $request->filled('difficulty') ? $request->difficulty : $lesson->difficulty,
            'quarter' => $request->filled('quarter') ? $request->quarter : $lesson->quarter,
            'objectives' => $request->filled('objectives') ? $request->objectives : $lesson->objectives,
            'is_draft' => $request->has('is_draft') ? $request->is_draft : $lesson->is_draft
        ]);
    
// Handle content sections
// In your update method
if ($request->filled('content_sections')) {
    // Get all existing content IDs for this lesson
    $existingContentIds = $lesson->contents()->pluck('id')->toArray();
    $submittedContentIds = [];
    
    foreach ($request->content_sections as $index => $sectionData) {
        $content = isset($sectionData['id']) 
            ? LessonContent::find($sectionData['id'])
            : new LessonContent();
            
        $content->fill([
            'lesson_id' => $lesson->id,
            'content' => $sectionData['content'],
            'image_name' => $sectionData['image_name'],
            'order' => $index
        ])->save();
        
        // Track which content IDs were submitted
        if ($content->id) {
            $submittedContentIds[] = $content->id;
        }
        
        // Handle new media uploads
        if (isset($sectionData['images'])) {
            foreach ($sectionData['images'] as $image) {
                $this->storeMedia($image, $content);
            }
        }
    }
    
    // Delete any content sections that weren't submitted
    $contentToDelete = array_diff($existingContentIds, $submittedContentIds);
    if (!empty($contentToDelete)) {
        LessonContent::whereIn('id', $contentToDelete)->delete();
    }
}

    // Handle media deletions
    if ($request->filled('media_to_delete')) {
        Media::whereIn('id', $request->media_to_delete)->delete();
    }

    // Handle new attachments
    if ($request->hasFile('attachments')) {
        foreach ($request->file('attachments') as $file) {
            $this->storeMedia($file, $lesson);
        }
    }

    // Handle attachment deletions
    if ($request->filled('attachments_to_delete')) {
        Media::whereIn('id', $request->attachments_to_delete)->delete();
    }

    return response()->json($lesson->load(['contents', 'media']));
    }

    public function destroy($id)
    {
        $lesson = Lesson::findOrFail($id);
        $lesson->delete();
        return response()->json(['message' => 'Lesson deleted successfully']);
    }

    protected function storeMedia($file, $model)
    {
        $path = $file->store('uploads/' . strtolower(class_basename($model)), 'public');
        
        return Media::create([
            'model_type' => get_class($model),
            'model_id' => $model->id,
            'file_path' => $path,
            'file_type' => $file->getClientMimeType(),
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize()
        ]);
    }

    public function drafts()
    {
        $drafts = Lesson::where('is_draft', true)
            ->with(['contents', 'media'])
            ->get();

        return response()->json($drafts);
    }

    public function publish($id)
    {
        $lesson = Lesson::findOrFail($id);
        $lesson->update(['is_draft' => false]);

        return response()->json($lesson);
    }
}