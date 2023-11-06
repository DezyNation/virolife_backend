<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        
        $id = $request->donatable_id;
        $parent = User::find($id);
        if (!$parent->primary_activated) {
            return response("Parent is not eligible to accept donations.", 403);
        }
        return $next($request);
    }
}
