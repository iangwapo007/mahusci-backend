<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\UserRequest;
use App\Http\Controllers\Controller;
use App\Models\Teacher;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;



class AuthController extends Controller
{
    /**
     * Display the specified resource.
     */
    public function login(UserRequest $request)
{
    $user = User::where('username', $request->username)->first() ?? $user = Teacher::where('username', $request->username)->first();
    ;

    if (! $user || ! Hash::check($request->password, $user->password)) {
        throw ValidationException::withMessages([
            'username' => ['The provided credentials are incorrect.'],
        ]);
    }

    $user->tokens()->delete();

    $token = $user->createToken($request->username)->plainTextToken;

    return [
        'user' => $user,
        'token' => $token,
        'role' => $user->role,
        'data' => $user
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
