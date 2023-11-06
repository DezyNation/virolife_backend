<?php

namespace App\Http\Controllers\Subscription;

use App\Models\Plan;
use App\Models\User;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Subscription\PointController;

class SubscriptionController extends PointController
{
    public function __construct()
    {
        $this->middleware('subscribe', ['only' => ['store']]);
        $this->middleware('senior_plan', ['only' => ['store']]);
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'planId' => 'required|exists:plans,id',
            'parentId' => 'exists:users,id'
        ]);
        // Plan::find($request->planId);
        $user_id = auth()->user()->id;
        $parent_id = $request->parentId;
        $count = DB::table('subscriptions')->where(['parent_id' => $parent_id, 'plan_id' => $request->planId])->count();
        if ($count >= 4) {
            return response("Choose different senior", 404);
        }
        if ($request['paymentMethod'] == 'gift') {
            $bool = DB::table('gifts')->where(['user_id' => $user_id, 'plan_id' => $request['planId'], 'redeemed' => 0, 'code' => $request['giftCard']])->exists();
            if ($bool) {
                DB::table('gifts')->where(['user_id' => $user_id, 'plan_id' => $request['planId'], 'redeemed' => 0, 'code' => $request['giftCard']])->update([
                    'redeemed' => 1,
                    'updated_at' => now()
                ]);
                $this->purchaseSubscription($request);
            } else {
                return response("Invalid gift card.", 400);
            }
        } else {
            $this->purchaseSubscription($request);
            //Payment gateway things
        }
    }

    public function purchaseSubscription(Request $request)
    {
        if ($request['paymentMethod'] == 'gateway') {
            $request->validate([
                'transactionId' => 'required|unique:subscriptions,id'
            ]);
            $plan = Plan::find($request['planId']);
            $this->gatewayPayment($request['transactionId'], $plan->price, 'subscription', json_encode($request->all()));
        }
        $user_id = auth()->user()->id;
        $parent_id = $request->parentId;
        // return $count;
        $subscription = Subscription::create([
            'user_id' => auth()->user()->id,
            'plan_id' => $request->planId,
            'parent_id' => $request->parentId,
            'transaction_id' => $request['transactionId'] ?? null
        ]);

        //This to be done after payment success

        $parent_plan = Subscription::where('user_id', $parent_id)->first();
        if (!is_null($request->referralId) || !empty($request->referralId)) {
            $this->referrals($request, $subscription->plan_id, 1);
        }
        $this->parentCommission(auth()->user()->id, $subscription->plan_id, auth()->user()->id);
        $this->commissionPoints($request['parentId'], auth()->user()->id, 1, 'parent');
        if ($parent_plan->plan_id == $subscription->plan_id) {
            // $this->commissionPoints($request['parentId'], auth()->user()->id, 1, 'chain');
            $users = DB::select("
        WITH RECURSIVE user_tree AS (
        SELECT u.id, u.parent_id, u.user_id, 2 as level
        FROM subscriptions u

        WHERE u.user_id = :userId
        UNION
        SELECT u2.id, u2.parent_id, u2.user_id, user_tree.level + 1
        FROM user_tree
        INNER JOIN subscriptions as u2 ON user_tree.parent_id= u2.user_id
        )
        SELECT *
        FROM user_tree ut
        ", ['userId' => $request['parentId']]);
            Log::info($users);
            foreach ($users as $user) {
                $this->commissionPoints($user->parent_id, $user->user_id, $user->level, 'chain');
            }
        }

        return $subscription;
    }

    public function purchaseSubscriptionGateway($plan_id, $parent_id, $user_id, $referral_id = null)
    {
        $user_id = $user_id;
        $parent_id = $parent_id;
        $subscription = Subscription::create([
            'user_id' => $user_id,
            'plan_id' => $plan_id,
            'parent_id' => $parent_id
        ]);

        //This to be done after payment success

        $parent_plan = Subscription::where('user_id', $parent_id)->first();
        if (!is_null($referral_id) || !empty($referral_id)) {
            $this->referralsGateway($referral_id, $subscription->plan_id, 1, $user_id);
        }
        $this->parentCommission($user_id, $subscription->plan_id, $user_id);
        $this->commissionPointsGateway($parent_id, $user_id, 1, 'parent', $user_id);
        if ($parent_plan->plan_id == $subscription->plan_id) {
            $users = DB::select("
        WITH RECURSIVE user_tree AS (
        SELECT u.id, u.parent_id, u.user_id, 2 as level
        FROM subscriptions u

        WHERE u.user_id = :userId
        UNION
        SELECT u2.id, u2.parent_id, u2.user_id, user_tree.level + 1
        FROM user_tree
        INNER JOIN subscriptions as u2 ON user_tree.parent_id= u2.user_id
        )
        SELECT *
        FROM user_tree ut
        ", ['userId' => $parent_id]);
            Log::info($users);
            foreach ($users as $user) {
                $this->commissionPointsGateway($user->parent_id, $user->user_id, $user->level, 'chain', $user_id);
            }
        }

        return $subscription;
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

    public function authUserSubs()
    {
        return Subscription::with('plan')->where('user_id', auth()->user()->id)->get();
    }
}
