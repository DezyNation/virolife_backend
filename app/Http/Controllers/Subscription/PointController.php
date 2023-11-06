<?php

namespace App\Http\Controllers\Subscription;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Point;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PointController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Point::with('plan')->all();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'planId' => 'required|exists:plans,id',
            'level' => 'required|min:0'
        ]);

        $point = Point::create([
            'plan_id' => $request['planId'],
            'level' => $request['level'],
        ]);

        return $point;
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function commissionPoints($parent_id, int $user_id, int $level, $purpose)
    {
        Log::info('data', [$parent_id, $user_id, $level]);
        // return true;
        if (is_null($parent_id)) {

            Log::info('no mapp null');
            return true;
        }

        $user = User::find($parent_id);
        $parent_plan = Subscription::where('user_id', $parent_id)->first();
        $user_plan = Subscription::where('user_id', $user_id)->first();
        if (!$user_plan || !$parent_plan) {
            # code...
            Log::info('no plan');
            return true;
        }
        if ($parent_plan->plan_id == $user_plan->plan_id) {
            $point = Point::where(['plan_id' => $parent_plan->plan_id, 'level' => $level])->first();
            if (!$point) {
                Log::info('no points');
                return response("No mapping found for the given combination", 404);
            }
            $transaction_id = uniqid("VIR");
            $opening_balance = $user->points;
            $closing_balance = $opening_balance + $point->point;
            $metadata = [
                'status' => true,
                'event' => 'points.credit',
                'beneficiary' => $user->name,
                'entity' => 'points',
                'credit' => $point->points,
                'debit' => 0,
            ];
            Log::info('mess', $metadata);
            $this->distributionTable($parent_id, $user_plan->plan_id, $point->points, $purpose);
            $this->transaction($parent_id, null, $transaction_id, 'App\Models\Point', $point->id, $point->points, 0, $opening_balance, $closing_balance, json_encode($metadata));
        }
    }

    public function commissionPointsGateway($parent_id, int $user_id, int $level, $purpose, $auth_user)
    {
        Log::info('data', [$parent_id, $user_id, $level]);
        // return true;
        if (is_null($parent_id)) {

            Log::info('no mapp null');
            return true;
        }

        $user = User::find($parent_id);
        $parent_plan = Subscription::where('user_id', $parent_id)->first();
        $user_plan = Subscription::where('user_id', $user_id)->first();
        if (!$user_plan || !$parent_plan || !$user) {
            # code...
            return true;
        }
        if ($parent_plan->plan_id == $user_plan->plan_id) {
            $point = Point::where(['plan_id' => $parent_plan->plan_id, 'level' => $level])->first();
            if (!$point) {
                Log::info('no points');
                return response("No mapping found for the given combination", 404);
            }

            $this->distributionTableGateway($parent_id, $user_plan->plan_id, $point->points, $purpose, $auth_user);
        }
    }

    public function distributionTableGateway(int $benefciary_id, int $plan_id, float $points, $purpose, int $user_id)
    {
        DB::table('point_distribution')->insert([
            'user_id' => $user_id,
            'beneficiary_id'   => $benefciary_id,
            'plan_id'   => $plan_id,
            'purpose' => $purpose,
            'expiry_at' => Carbon::now()->addYears(5),
            'points' => $points,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $user = User::find($user_id);
        $final_points = $user->health_points + $points;
        User::where('id', $user_id)->update([
            'health_points' => $final_points
        ]);
        return true;
    }

    public function distributionTable(int $user_id, int $plan_id, float $points, $purpose)
    {
        DB::table('point_distribution')->insert([
            'user_id' => auth()->user()->id,
            'beneficiary_id'   => $user_id,
            'plan_id'   => $plan_id,
            'purpose' => $purpose,
            'expiry_at' => Carbon::now()->addYears(5),
            'points' => $points,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $user = User::find($user_id);
        $final_points = $user->health_points + $points;
        User::where('id', $user_id)->update([
            'health_points' => $final_points
        ]);
        return true;
    }

    public function points($id)
    {
        $data = DB::table('point_distribution')
            ->where('point_distribution.beneficiary_id', $id)
            ->join('users', 'users.id', '=', 'point_distribution.user_id')
            ->join('subscriptions', 'subscriptions.user_id', '=', 'point_distribution.user_id')
            ->join('users as beneficiary', 'beneficiary.id', '=', 'point_distribution.beneficiary_id')
            ->join('plans', 'plans.id', '=', 'point_distribution.plan_id')
            ->select('users.name as user_name', 'users.phone_number as user_phone', 'subscriptions.parent_id as parent_id', 'beneficiary.name as beneficiary_name', 'beneficiary.phone_number as beneficiary_phone', 'plans.name', 'point_distribution.*')
            ->get();
        return $data;
    }

    public function referrals(Request $request, $plan_id, $level)
    {
        $request->validate([
            'referralId' => 'required|exists:users,id'
        ]);

        $point = Point::where(['plan_id' => $plan_id, 'level' => $level])->first();
        if (!$point) {
            Log::info('no points');
            return response("No mapping found for the given combination", 404);
        }

        $user = User::where('id', $request->referralId)->first();
        // Log::info(['user_pt' => $user]);
        if ($user) {
            DB::table('point_distribution')->insert([
                'user_id' => auth()->user()->id,
                'beneficiary_id'   => $request->referralId,
                'plan_id'   => $plan_id,
                'purpose' => 'referrals',
                'expiry_at' => Carbon::now()->addYears(5),
                'points' => $point->points,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            // Log::info('h points should begin');
            // $user->update(['health_points' => $user->health_points+$point->points]);
            $transaction_id = uniqid("VIR");
            $opening_balance = $user->points;
            $closing_balance = $opening_balance + $point->point;
            $metadata = [
                'status' => true,
                'event' => 'points.credit',
                'description' => "Referral in subscription",
                'beneficiary' => $user->name,
                'entity' => 'points',
                'credit' => $point->points,
                'debit' => 0,
            ];

            $this->transaction($user->id, null, $transaction_id, 'App\Models\Point', $point->id, $point->points, 0, $opening_balance, $closing_balance, json_encode($metadata));
        }
    }

    public function referralsGateway($referral_id, $plan_id, $level, $user_id)
    {

        $point = Point::where(['plan_id' => $plan_id, 'level' => $level])->first();
        if (!$point) {
            Log::info('no points');
            return response("No mapping found for the given combination", 404);
        }

        $user = User::where('id', $referral_id)->first();
        // Log::info(['user_pt' => $user]);
        if ($user) {
            DB::table('point_distribution')->insert([
                'user_id' => $user_id,
                'beneficiary_id'   => $referral_id,
                'plan_id'   => $plan_id,
                'purpose' => 'referrals',
                'expiry_at' => Carbon::now()->addYears(5),
                'points' => $point->points,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            // Log::info('h points should begin');
            // $user->update(['health_points' => $user->health_points+$point->points]);
            // $transaction_id = uniqid("VIR");
            // $opening_balance = $user->points;
            // $closing_balance = $opening_balance + $point->point;
            // $metadata = [
            //     'status' => true,
            //     'event' => 'points.credit',
            //     'description' => "Referral in subscription",
            //     'beneficiary' => $user->name,
            //     'entity' => 'points',
            //     'credit' => $point->points,
            //     'debit' => 0,
            // ];

            // $this->transaction($user->id, null, $transaction_id, 'App\Models\Point', $point->id, $point->points, 0, $opening_balance, $closing_balance, json_encode($metadata));
        }
    }

    public function parentCommission($user_id, $plan_id, $subscriber)
    {
        $data = DB::table('user_parent')->where('user_id', $user_id)->first();
        if (!$data || is_null($data) || empty($data)) {
            return response("No parent was found.");
        }

        $user = User::find($data->parent_id);
        $role = $user->getRoleNames()[0];

        $plan = Plan::find($plan_id);

        $amount = $plan->{$role};
        // Log::info(['comm-amt' => $amount]);
        // $this->parentCommission($data->parent_id, $plan_id, $subscriber);
        // return true;

        DB::table('plan_commission')->insert([
            'user_id' => $data->parent_id,
            'plan_id' => $plan_id,
            'subscriber_id' => $subscriber,
            'credit' => $amount,
            'opening_balance' => $user->wallet,
            'closing_balance' => $user->wallet + $amount,
            'created_at' => now(),
            'created_at' => now()
        ]);

        $user->update(['wallet' => $user->wallet + $amount]);

        // $transaction_id = uniqid("VIR-COMM");
        // $metadata = [
        //     'status' => true,
        //     'event' => 'amount.credit',
        //     'beneficiary' => $user->name,
        //     'entity' => 'amount',
        //     'credit' => $amount,
        //     'debit' => 0,
        // ];
        // Log::info('mess', $metadata);
        // $this->transaction($user->id, null, $transaction_id, 'App\Models\Plan', $plan->id, $amount, 0, $user->wallet, $user->wallet + $amount, json_encode($metadata));
        // $user->update(['wallet' => $user->wallet + $amount]);
        $this->parentCommission($data->parent_id, $plan_id, $subscriber);
    }
}
