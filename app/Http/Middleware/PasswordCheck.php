<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class PasswordCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $request->validate([
            'password' => 'required',
        ]);
        $user = User::find(auth()->user()->id);
        if (!Hash::check($request['password'], $user->password)) {
            return response("The password you have entered is wrong.", 401);
        }

        return $next($request);
    }
}
