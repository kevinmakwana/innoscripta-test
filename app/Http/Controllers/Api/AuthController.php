<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * Simple auth controller providing register/login/logout endpoints using
 * Sanctum personal access tokens.
 */
class AuthController extends Controller
{
    /**
     * Register a new user and return a personal access token (201).
     */
    public function register(Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json(['message' => 'User registered successfully', 'data' => ['token' => $token]], 201);
    }

    /**
     * Login and return a personal access token.
     */
    public function login(Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials', 'data' => new \stdClass], 401);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json(['message' => 'Login successful', 'data' => ['token' => $token]], 200);
    }

    /**
     * Revoke the current token.
     */
    public function logout(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out', 'data' => new \stdClass], 200);
    }
}
