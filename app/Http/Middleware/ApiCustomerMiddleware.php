<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiCustomerMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (!auth('customer')->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Customer authentication required.',
            ], 401);
        }
        return $next($request);
    }
}
