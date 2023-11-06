<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class LoopCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $params = $request->route()->parameters();
        $array =  DB::select("
        WITH RECURSIVE user_tree AS (
        SELECT u.id, u.name, u.parent_id, u.phone_number, u.email
        FROM users u
        WHERE u.parent_id = :userId
        UNION
        SELECT u2.id, u2.name, u2.parent_id, u2.phone_number, u2.email
        FROM user_tree
        INNER JOIN users as u2 ON user_tree.id = u2.parent_id
        )
        SELECT *
        FROM user_tree ut
        ", ['userId' => $params['id']]);

        if (in_array($params['id'], array_column($array, 'id'))) {
            return response("You can not become senior of your seniors", 400);
        }

        return $next($request);
    }
}
