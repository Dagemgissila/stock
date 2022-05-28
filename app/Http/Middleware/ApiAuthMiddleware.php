<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiAuthMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        try {
            if (!auth('api')->check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated. Please login.',
                ], 401);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token expired or invalid. Please refresh.',
            ], 401);
        }

        return $next($request);
    }
}
