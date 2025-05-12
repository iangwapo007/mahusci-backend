<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sequence;
use App\Models\SequenceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SequenceController extends Controller
{
    public function index()
    {
        $sequences = Sequence::where('teacher_id', Auth::id())  
            ->with('items')
            ->get();

        return response()->json($sequences);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'items' => 'required|array|min:1',
            'quarter' => 'required|in:1,2,3,4',
            'items.*.id' => 'required|integer',
            'items.*.type' => 'required|in:lesson,assessment',
            'items.*.title' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $sequence = Sequence::create([
            'title' => $request->title,
            'teacher_id' => Auth::id(), 
            'quarter' => $request->quarter,
        ]);

        foreach ($request->items as $position => $item) {
            SequenceItem::create([
                'sequence_id' => $sequence->id,
                'item_id' => $item['id'],
                'item_type' => $item['type'],
                'position' => $position,
                'title' => $item['title'],
            ]);
        }

        return response()->json([
            'message' => 'Sequence created successfully',
            'sequence' => $sequence->load('items')
        ], 201);
    }

    public function show(Sequence $sequence)
    {
        return response()->json($sequence->load('items'));
    }

    public function update(Request $request, Sequence $sequence)
    {

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'items' => 'sometimes|required|array|min:1',
            'items.*.id' => 'required|integer',
            'items.*.type' => 'required|in:lesson,assessment',
            'items.*.title' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if ($request->has('title')) {
            $sequence->update(['title' => $request->title]);
        }

        if ($request->has('items')) {
            $sequence->items()->delete();
            
            foreach ($request->items as $position => $item) {
                SequenceItem::create([
                    'sequence_id' => $sequence->id,
                    'item_id' => $item['id'],
                    'item_type' => $item['type'],
                    'position' => $position,
                    'title' => $item['title'],
                ]);
            }
        }

        return response()->json([
            'message' => 'Sequence updated successfully',
            'sequence' => $sequence->load('items')
        ]);
    }

    public function destroy(Sequence $sequence)
    {
       
        $sequence->delete();

        return response()->json(['message' => 'Sequence deleted successfully']);
    }
}