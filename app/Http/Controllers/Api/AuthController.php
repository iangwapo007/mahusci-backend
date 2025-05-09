<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\UserRequest;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;



class AuthController extends Controller
{
    /**
     * Display the specified resource.
     */
    public function login(UserRequest $request)
{
    $user = User::where('username', $request->username)->first();

    if (! $user || ! Hash::check($request->password, $user->password)) {
        throw ValidationException::withMessages([
            'username' => ['The provided credentials are incorrect.'],
        ]);
    }

    // ❌ Delete previous tokens for this user
    $user->tokens()->delete();

    // ✅ Create a new single token
    $token = $user->createToken($request->username)->plainTextToken;

    return [
        'user' => $user,
        'token' => $token,
        'role' => $user->role,
        'id' => $user->id,
    ];
}

     /**
     * Display the specified resource.
     */
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        $response = [
            'message' => 'Logged out'
        ];

        return $response;
    }
}
