<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CurriculumSequence;
use App\Models\CurriculumSequenceItem;
use App\Models\CurriculumSequenceQuarter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CurriculumSequenceController extends Controller
{
   public function index()
    {
        $sequences = CurriculumSequence::with(['quarters.items.sequence.items'])
            ->where('teacher_id', Auth::id()) 
            ->get()
            ->map(function ($sequence) {
                return [
                    'id' => $sequence->id,
                    'title' => $sequence->title,
                    'description' => $sequence->description,
                    'curriculum_image' => $sequence->curriculum_image,
                    'grades' => $sequence->grades,
                    'is_draft' => $sequence->is_draft,
                    'created_at' => $sequence->created_at,
                    'quarters' => $sequence->quarters->map(function ($quarter) {
                        return [
                            'quarter' => $quarter->quarter,
                            'sequences' => $quarter->items->map(function ($item) {

                                return [
                                    'sequence_id' => $item->sequence_id,
                                    'sequence_title' => $item->sequence->title,
                                    'sequence_type' => $item->sequence->items->first()?->item_type ?? 'lesson'
                                ];
                            })
                        ];
                    })
                ];
            });

        return response()->json($sequences);
    }

     public function forStudentIndex()
    {
        $sequences = CurriculumSequence::with(['quarters.items.sequence.items'])
            ->where('is_draft', "published") 
            ->get()
            ->map(function ($sequence) {
                return [
                    'id' => $sequence->id,
                    'title' => $sequence->title,
                    'description' => $sequence->description,
                    'curriculum_image' => $sequence->curriculum_image,
                    'grades' => $sequence->grades,
                    'is_draft' => $sequence->is_draft,
                    'created_at' => $sequence->created_at,
                    'quarters' => $sequence->quarters->map(function ($quarter) {
                        return [
                            'quarter' => $quarter->quarter,
                            'sequences' => $quarter->items->map(function ($item) {

                                return [
                                    'sequence_id' => $item->sequence_id,
                                    'sequence_title' => $item->sequence->title,
                                    'sequence_type' => $item->sequence->items->first()?->item_type ?? 'lesson'
                                ];
                            })
                        ];
                    })
                ];
            });

        return response()->json($sequences);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'curriculum_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
            'is_draft' => 'required|string',
            'grades.*' => 'in:7,8,9,10,11,12',
            'quarter_sequences' => 'required|array:1,2,3,4',
            'quarter_sequences.*' => 'array',
            'quarter_sequences.*.*.sequence_id' => 'required|exists:sequences,id',
            'quarter_sequences.*.*.quarter' => 'required|integer|between:1,4',
        ]);

        // Handle image upload
        $imagePath = null;
        if ($request->hasFile('curriculum_image')) {
            $imagePath = $request->file('curriculum_image')->store('curriculum_images', 'public');
        }

        $sequence = CurriculumSequence::create([
            'title' => $request->title,
            'description' => $request->description,
            'teacher_id' => Auth::id(),
            'curriculum_image' => $imagePath,
            'is_draft' => $request->is_draft,
            'grades' => implode(',', $request->grades),
        ]);

        foreach ($request->quarter_sequences as $quarter => $sequences) {
            $quarterRecord = CurriculumSequenceQuarter::create([
                'curriculum_sequence_id' => $sequence->id,
                'quarter' => $quarter,

            ]);

            foreach ($sequences as $index => $seq) {
                CurriculumSequenceItem::create([
                    'curriculum_sequence_quarter_id' => $quarterRecord->id,
                    'sequence_id' => $seq['sequence_id'],
                    'order' => $index,
                ]);
            }
        }

        return response()->json($sequence->load(['quarters.items.sequence']), 201);
    }

    public function show(CurriculumSequence $curriculumSequence)
    {
        // $this->authorize('view', $curriculumSequence);

        return response()->json($curriculumSequence->load(['quarters.items.sequence.items']));
    }

    public function update(Request $request, CurriculumSequence $curriculumSequence)
    {
        // Log::info('value: ', ['cs:' => $curriculumSequence, 'request'=> $request]);
        // $this->authorize('update', $curriculumSequence);

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'curriculum_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
            'is_draft' => 'required|string',
            'grades.*' => 'in:7,8,9,10,11,12',
            'quarter_sequences' => 'required|array:1,2,3,4', // ensures all 4 quarters are present
            'quarter_sequences.*' => 'array',
            'quarter_sequences.*.*.sequence_id' => 'required|exists:sequences,id',
            'quarter_sequences.*.*.quarter' => 'required|integer|between:1,4',
        ]);

          // Handle image upload
        $imagePath = null;
        if ($request->hasFile('curriculum_image')) {
            $imagePath = $request->file('curriculum_image')->store('curriculum_images', 'public');
        }

        $curriculumSequence->update([
            'title' => $request->title,
            'description' => $request->description,
            'curriculum_image' => $imagePath,
            'is_draft' => $request->is_draft,
            'grades' => implode(',', $request->grades),
        ]);

      

        // Delete existing quarters and items
        $curriculumSequence->quarters()->delete();

        // Create new quarters and items
        foreach ($request->quarter_sequences as $quarter => $sequences) {
            $quarterRecord = CurriculumSequenceQuarter::create([
                'curriculum_sequence_id' => $curriculumSequence->id,
                'quarter' => $quarter,
            ]);

            foreach ($sequences as $index => $seq) {
                CurriculumSequenceItem::create([
                    'curriculum_sequence_quarter_id' => $quarterRecord->id,
                    'sequence_id' => $seq['sequence_id'],
                    'order' => $index,
                ]);
            }
        }

        return response()->json($curriculumSequence->load(['quarters.items.sequence']));
    }

    public function destroy(CurriculumSequence $curriculumSequence)
    {
        $this->authorize('delete', $curriculumSequence);

        $curriculumSequence->delete();

        return response()->json(null, 204);
    }
}
