<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalLogin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = User::with('roles')->where('email', $request['email'])->first();

        if (empty($user)) {
            return response("User doesn't exists, contact admins.", 400);
        }

        $role = $user['roles'];
        if (empty($role)) {
            return response("User doesn't have assigned role, contact admins", 400);
        }

        $role = $role[0]['name'];

        if ($role == 'admin') {
            return response("Admins are not allowed to login through here", 400);
        }

        return $next($request);
    }
}
