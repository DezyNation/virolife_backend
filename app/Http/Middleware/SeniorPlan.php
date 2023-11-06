<?php

namespace App\Http\Middleware;

use App\Models\Subscription;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SeniorPlan
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $parent_id = $request['parentId'];
        $subscription = Subscription::where(['user_id' => $parent_id]);

        if (!$subscription->exists() || empty($subscription)) {
            return response("Selected senior is not enrolled in any plan.", 404);
        }
        return $next($request);
    }
}
