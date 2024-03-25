<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;

class ApiAuthMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        try {
            $user = auth('api')->userOrFail();
            // Inject company_id to prevent client-supplied value trust
            $request->merge(['_company_id' => $user->company_id]);
        } catch (\PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException) {
            return response()->json(['success'=>false,'message'=>'Token expired. Please refresh.'], 401);
        } catch (\PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException) {
            return response()->json(['success'=>false,'message'=>'Token invalid.'], 401);
        } catch (\PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException) {
            return response()->json(['success'=>false,'message'=>'Token absent.'], 401);
        }
        return $next($request);
    }
}
