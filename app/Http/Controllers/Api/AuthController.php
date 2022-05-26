<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AuthController extends ApiBaseController
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if (!$token = auth('api')->attempt($credentials)) {
            return $this->sendError('Invalid email or password.', [], 401);
        }

        $user = auth('api')->user()->load('role');
        return $this->sendResponse([
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => config('jwt.ttl') * 60,
            'user'         => $user,
        ], 'Login successful');
    }

    public function logout(): JsonResponse
    {
        auth('api')->logout();
        return $this->sendResponse([], 'Successfully logged out');
    }

    public function refresh(): JsonResponse
    {
        $newToken = auth('api')->refresh();
        return $this->sendResponse([
            'access_token' => $newToken,
            'token_type'   => 'bearer',
            'expires_in'   => config('jwt.ttl') * 60,
        ], 'Token refreshed');
    }

    public function profile(): JsonResponse
    {
        $user = auth('api')->user()->load(['role', 'company', 'warehouses']);
        return $this->sendResponse($user, 'Profile fetched');
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        $user->update($request->only(['name', 'phone', 'profile_image']));
        return $this->sendResponse($user->fresh(), 'Profile updated');
    }
}
