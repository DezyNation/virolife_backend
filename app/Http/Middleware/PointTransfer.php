<?php

namespace App\Http\Middleware;

use Closure;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class PointTransfer
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $points = $request['points'];
        $user_points = DB::table('point_distribution')->where(['beneficiary_id' => auth()->user()->id])->where(function ($q) {
            $q->where('expiry_at', '>', Carbon::today())
                ->orWhere('expiry_at', null);
        })

            ->sum('points');
        if ($points > $user_points) {
            return response("You don't have enough points to transfer", 403);
        }
        return $next($request);
    }
}
