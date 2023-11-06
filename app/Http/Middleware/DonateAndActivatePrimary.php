<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class DonateAndActivatePrimary
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $group = $request['group'];
        $bool = DB::table('donations')->where(['donatable_type' => 'App\Models\User', 'group' => $group, 'user_id' => auth()->user()->id])->count();
        if ($bool >= 10) {
            $column = $group . "_activated";
            DB::table('users')->where('id', auth()->user()->id)->update([
                $column => 1
            ]);
        }
        return $next($request);
    }
}
