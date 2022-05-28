<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiSuperAdminMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = auth('api')->user();
        if (!$user || !$user->is_superadmin) {
            return response()->json([
                'success' => false,
                'message' => 'Super admin access required.',
            ], 403);
        }
        return $next($request);
    }
}
