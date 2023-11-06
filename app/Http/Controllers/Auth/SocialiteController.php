<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\JWT;

class SocialiteController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        $socialite = Socialite::driver('google')->user();
        $user = User::updateOrCreate(
            [
                'provider' => 'google',
                'provider_id' => $socialite->id
            ],
            [
                'name' => $socialite->name,
                'email' => $socialite->email,
                'profile' => $socialite->avatar
            ]
        );

        $token = JWTAuth::fromUser($user);

        return $token;
    }
}
