<?php

use App\Models\User;
use App\Models\Group;
use App\Mail\LoginMail;
use Illuminate\Support\Arr;
use App\Models\Donation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\SocialiteController;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    // $role = 'user';
    
    // $users = DB::table('users')
    //     ->join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
    //     ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
    //     ->where('roles.name', '=', $role)
    //     ->join('donations', 'donations.donated_to', '=', 'users.id', 'left')
    //     ->join('point_distribution', 'point_distribution.beneficiary_id', '=', 'users.id', 'left')
    //     ->select('users.id', 'users.name', 'users.email', 'users.phone_number', 'users.dob', 'users.stars', 'users.ad_points', 'users.created_at', DB::raw('stars/(
    //         (YEAR(NOW()) - YEAR(users.created_at)*12) + (MONTH(NOW()) - MONTH(users.created_at)+ 1)
    //         ) as performance'),
    //     )
    //     ->groupBy('users.id', 'users.name', 'users.email', 'users.phone_number', 'users.dob', 'users.stars', 'users.ad_points', 'users.created_at')
    //     ->get();
        
    //     $points =  DB::table('users')
    //     ->join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
    //     ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
    //     ->where('roles.name', '=', $role)
    //     ->leftJoin('point_distribution', function($join) {
    //         $join->on('point_distribution.beneficiary_id', '=', 'users.id');
    //         $join->where('expiry_at', '>', Carbon::now());
    //     })
    //     ->select('users.id', DB::raw('sum(point_distribution.points) as points'))
    //     ->groupBy('users.id')
    //     ->get();
        
    //     $primary_sum =  DB::table('users')
    //     ->join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
    //     ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
    //     ->where('roles.name', '=', $role)
    //     ->leftJoin('donations', function($join) {
    //         $join->on('donations.donated_to', '=', 'users.id');
    //         $join->where('donations.group', '=', 'primary');
    //     })
    //     ->select('users.id', DB::raw('sum(amount) as primary_sum'))
    //     ->groupBy('users.id')
    //     ->get();
        
    //     $secondary_sum =  DB::table('users')
    //     ->join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
    //     ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
    //     ->where('roles.name', '=', $role)
    //     ->leftJoin('donations', function($join) {
    //         $join->on('donations.donated_to', '=', 'users.id');
    //         $join->where('donations.group', '=', 'secondary');
    //     })
    //     ->select('users.id', DB::raw('sum(amount) as secondary_sum'))
    //     ->groupBy('users.id')
    //     ->get();
        
    // $result = $users
    // ->concat($points)
    // ->concat($primary_sum)
    // ->concat($secondary_sum)
    // ->groupBy('id')
    // ->map(function ($items) {
    //     return $items->reduce(function ($merged, $item) {
    //         return array_merge($merged, (array) $item);
    //     }, []);
    // })
    // ->values();
    
    // return $result;
      
    return ['Laravel' => app()->version()];
});

require __DIR__ . '/auth.php';
