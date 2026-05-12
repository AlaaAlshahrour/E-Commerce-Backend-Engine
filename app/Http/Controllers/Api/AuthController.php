<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use App\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return ResponseHelper::jsonResponse([
            'user' => $user,
            'token' => $token,
        ], 'User registered successfully', 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();
        if (! Auth::attempt($data)) {
            return ResponseHelper::jsonResponse(null, 'Invalid credentials', 401, false);
        }

        $user = Auth::user();
        /** @var User $user */
        $token = $user->createToken('auth_token')->plainTextToken;

        return ResponseHelper::jsonResponse([
            'user' => $user,
            'token' => $token,
        ], 'Login successful');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
//        $request->user()->tokens()->delete();

        return ResponseHelper::jsonResponse(null, 'Logged out successfully');
    }

    public function me(Request $request): JsonResponse
    {
        return ResponseHelper::jsonResponse($request->user(), 'User profile retrieved');
    }
}
