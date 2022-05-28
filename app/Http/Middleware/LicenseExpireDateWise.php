<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class LicenseExpireDateWise
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = auth('api')->user();
        if ($user && $user->company) {
            $plan = $user->company->subscriptionPlan;
            if ($plan && $plan->expires_at && $plan->expires_at->isPast()) {
                $grace = $plan->expires_at->addDays(7);
                if (now()->greaterThan($grace)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Your subscription has expired. Please renew to continue.',
                    ], 402);
                }
            }
        }
        return $next($request);
    }
}
