<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LearningHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LearningHistoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return LearningHistory::where('student_id', Auth::id())->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string|max:255',
            'details' => 'required|string',
            'subject' => 'required|string|max:255',
        ]);

        $validated['student_id'] = Auth::id();

        $history = LearningHistory::create($validated);
    
        return response()->json([
            'message' => 'Learning history recorded successfully.',
            'data' => $history
        ], 201);
    }
}
