<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class TeacherController extends Controller
{
    /**
     * Store a newly created teacher in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'firstname' => 'required|string|max:255',
            'middlename' => 'nullable|string|max:255',
            'lastname' => 'required|string|max:255',
            'age' => 'required|integer|min:18|max:120',
            'birthdate' => 'required|date',
            'school_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:teachers',
            'address' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:teachers',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();
        $validated['password'] = Hash::make($validated['password']);

        $teacher = Teacher::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Teacher created successfully',
            'data' => $teacher
        ], 201);
    }

    public function updateProfilePicture(Request $request)
    {
        $request->validate([
            'profile_picture' => 'required|image|mimes:jpeg,png,jpg,gif|max:5048',
        ]);
    
        $user = User::find(Auth::id()); // ensure this returns an Eloquent model
    
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }
    
        // Optional: delete the old picture if stored
        if ($user->profile_picture && Storage::disk('public')->exists($user->profile_picture)) {
            Storage::disk('public')->delete($user->profile_picture);
        }
    
        $path = $request->file('profile_picture')->store('profile_pictures', 'public');
    
        $user->update([
            'profile_picture' => $path,
        ]);
    
        return response()->json([
            'message' => 'Profile picture updated successfully.',
            'profile_picture_url' => asset('storage/' . $path),
        ]);
    }
    

    /**
     * Display the specified teacher.
     */
    public function show($id)
    {
        $teacher = Teacher::find($id);

        if (!$teacher) {
            return response()->json([
                'success' => false,
                'message' => 'Teacher not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $teacher
        ]);
    }

    /**
     * Update the specified teacher in storage.
     */
    public function update(Request $request, $id)
    {
        $teacher = Teacher::find($id);

        if (!$teacher) {
            return response()->json([
                'success' => false,
                'message' => 'Teacher not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'firstname' => 'sometimes|string|max:255',
            'middlename' => 'nullable|string|max:255',
            'lastname' => 'sometimes|string|max:255',
            'age' => 'sometimes|integer|min:18|max:120',
            'birthdate' => 'sometimes|date',
            'school_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:teachers,email,'.$teacher->id,
            'address' => 'sometimes|string|max:255',
            'username' => 'sometimes|string|max:255|unique:teachers,username,'.$teacher->id,
            'password' => 'sometimes|string|min:8|confirmed',
            'role' => 'sometimes|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $teacher->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Teacher updated successfully',
            'data' => $teacher
        ]);
    }

    /**
     * Remove the specified teacher from storage.
     */
    public function destroy($id)
    {
        $teacher = Teacher::find($id);

        if (!$teacher) {
            return response()->json([
                'success' => false,
                'message' => 'Teacher not found'
            ], 404);
        }

        $teacher->delete();

        return response()->json([
            'success' => true,
            'message' => 'Teacher deleted successfully'
        ]);
    }
}