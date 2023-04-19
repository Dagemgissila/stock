<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

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

        return $this->sendResponse($this->tokenResponse($token), 'Login successful');
    }

    public function refresh(Request $request): JsonResponse
    {
        // Redis mutex prevents concurrent refresh race condition
        $lockKey = 'jwt_refresh_' . auth('api')->id();
        $lock    = Cache::lock($lockKey, 5);

        if (!$lock->get()) {
            return $this->sendError('Refresh already in progress. Please retry.', [], 429);
        }

        try {
            $newToken = auth('api')->refresh();
            return $this->sendResponse($this->tokenResponse($newToken), 'Token refreshed');
        } finally {
            $lock->release();
        }
    }

    public function logout(): JsonResponse
    {
        auth('api')->logout();
        return $this->sendResponse([], 'Successfully logged out');
    }

    public function profile(): JsonResponse
    {
        return $this->sendResponse(
            auth('api')->user()->load(['roles', 'company', 'warehouses']),
            'Profile fetched'
        );
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        $user->update($request->only(['name', 'phone']));
        return $this->sendResponse($user->fresh(), 'Profile updated');
    }

    private function tokenResponse(string $token): array
    {
        return [
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => config('jwt.ttl') * 60,
        ];
    }
}
