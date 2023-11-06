<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['store']]);
    }

    public function checkCredentials(Request $request)
    {
        $request->only(['email', 'password']);
        $user = User::where('email', $request['email'])->first();
        if (!$user || !Hash::check($request['password'], $user->password)) {
            throw ValidationException::withMessages([
                'error' => ['Email and password does not match our records.']
            ]);
        }
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(Request $request): JsonResponse
    {
        $this->checkCredentials($request);
        $credentials = $request->only(['email', 'password']);
        if (!$token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $this->loginTable($request, auth()->user()->id);
        return $this->respondWithToken($token);
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(): JsonResponse
    {
        return response()->json(User::where('id', auth()->user()->id)->with(['subscription.plan', 'roles'])
        ->select('users.*', DB::raw('stars/((DATEDIFF(CURDATE() ,users.created_at)*0.032855)) as performance'))
        ->get());
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh(): JsonResponse
    {
        return $this->respondWithToken(auth()->refresh());
    }

    protected function respondWithToken($token): JsonResponse
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(): JsonResponse
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    public function loginTable(Request $request, $user_id)
    {
        DB::table('logins')->insert([
            'user_id' => $user_id,
            'ip' => $request->ip(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return true;
    }
}
