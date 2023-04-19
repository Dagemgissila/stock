<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ApiAuthMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        try {
            $user = auth('api')->userOrFail();

            // Prevent cross-company data leakage:
            // inject company_id into request so controllers never trust client input
            $request->merge(['_company_id' => $user->company_id]);

        } catch (\PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json(['success'=>false,'message'=>'Token expired. Please refresh.'], 401);
        } catch (\PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['success'=>false,'message'=>'Token invalid.'], 401);
        } catch (\PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['success'=>false,'message'=>'Token absent.'], 401);
        }

        return $next($request);
    }
}
