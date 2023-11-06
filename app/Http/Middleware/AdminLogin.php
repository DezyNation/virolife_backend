<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminLogin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        $user = User::with('roles')->where('email', $request['email'])->first();

        if (!$user->exists()) {
            return response("User doesn't exist in system, contact admins", 400);
        }

        $role = $user['roles'];
        if (sizeof($role) == 0) {
            return response("User doesn't have assigned role, contact admins", 400);
        }

        $role = $user['roles'][0]['name'];

        if ($role !== 'admin') {
            return response("User must be admin to login here.", 400);
        }
        return $next($request);
    }
}
