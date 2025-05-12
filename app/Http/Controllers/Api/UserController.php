<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\UserRequest;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return User::all();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UserRequest $request)
    {
        $validated = $request->validated();

        $validated['password'] = Hash::make($validated['password']);

        $user = user::create($validated);

        return $user;
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return user::findOrFail($id);
    }

    /**
     * Display current logged user details.
     */
    public function profile(Request $request)
    {

        return $request->user();

    }


    /**
     * Update the specified resource in storage.
     */
    public function update(string $id, UserRequest $request )
    {
        $user = User::findOrFail($id);

        $validated = $request->validated();

        $user->update([
            'firstname'   => $validated['firstname'],
            'middlename'  => $validated['middlename'] ?? null,
            'lastname'    => $validated['lastname'],
            'birthdate'   => $validated['birthdate'],
            'age'         => $validated['age'],
            'email'       => $validated['email'],
            'username'    => $validated['username'],
            'grade_level' => $validated['grade_level'],
            'school_name' => $validated['school_name'],
            'section'     => $validated['section'],
            'address'     => $validated['address'],
        ]);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user->fresh()
        ]);
    }


    public function email(UserRequest $request, string $id)
    {
        $user = User::find($id);

        $validated = $request->validated();
 
        $user->email = $validated['email'];

        return $user;
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
     * Update the password of the specified resource in storage.
     */
    public function password(UserRequest $request, string $id)
    {
        $user = User::find($id);

        $validated = $request->validated();
 
        $user->password = $validated['password'];
 
        $user->save();

        return $user;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = User::findOrFail($id);

        $user->delete();

        return $user;
    }
}