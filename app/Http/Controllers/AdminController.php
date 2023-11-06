<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Topup;
use App\Models\Campaign;
use App\Models\Donation;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use App\Exports\AllTeamDonationExport;
use App\Exports\UserExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TransferRequest;
use App\Exports\WithdrawalRequest;
use App\Exports\SubscriptionExport;
use App\Exports\LoginExport;
use App\Exports\PayoutExport;
use App\Exports\ReferralExport;

class AdminController extends Controller
{
    public function overview(): array
    {
        $users = $this->usersData();
        $campaigns = $this->campaigns();
        return [
            $users,
            $campaigns
        ];
    }

    public function usersData(): array
    {
        $user = User::latest();
        return [
            'users' => $user->take(5)->get(),
            'count' => $user->count()
        ];
    }

    public function campaigns(): array
    {
        $campaigns = Campaign::latest();
        return [
            'campaigns' => $campaigns->take(5)->get(),
            'count' => $campaigns->count(),
        ];
    }

    public function topUp(Request $request, string $id)
    {
        $request->validate([
            'amount' => 'required|numeric'
        ]);
        $amount = $request['amount'];
        $user = User::find($id);
        Topup::create([
            'user_id' => $id,
            'admin_id' => auth()->user()->id,
            'amount' => $amount
        ]);
        $user->update([
            'wallet' => $amount + $user->wallet
        ]);

        return response("Wallet Updated", 200);
    }

    public function changeRole(Request $request, $id)
    {
        $user = User::find($id);
        $user->syncRoles([$request['role']]);
    }

    public function childrenUser($id)
    {
        $users = DB::select("
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
        ", ['userId' => $id]);

        return $users;
    }

    public function parents($id)
    {
        $users = DB::select("
        WITH RECURSIVE user_tree AS (
        SELECT u.id, u0.parent_id as user_parent, u0.name as parent_name, u0.email as parent_email, u0.phone_number as parent_phone, u0.upi_id, u0.id_type, u0.primary_activated, u0.secondary_activated
        FROM users u
        INNER JOIN users as u0 ON u.parent_id = u0.id
        WHERE u.id = :userId
        UNION
        SELECT u2.id, u2.parent_id, u2.name, u2.phone_number, u2.email, u2.upi_id, u2.id_type, u2.primary_activated, u2.secondary_activated
        FROM user_tree
        INNER JOIN users as u2 ON user_tree.user_parent = u2.id
        )
        SELECT *
        FROM user_tree ut
        ", ['userId' => $id]);

        return $users;
    }
    
    public function storeUser(Request $request)
    {
        // return 0;
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:' . User::class],
            'role' => ['required', 'exists:roles,name'],
            'phone' => ['required', 'numeric'],
            'parentId' => ['required', 'exists:users,id']
            // 'password' => ['required', Rules\Password::defaults()],
            // 'code' => ['exists:users,id']
        ]);

        if ($request->has('code')) {
            if (DB::table('users')->where('parent_id', $request->code)->count() >= 4) {
                return response("Enter another code.", 400);
            }
        }
        $password = $request['password'];
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'active' => 0,
            'phone_number' => $request->phone,
            'parent_id' => $request->code ?? null,
            'password' => Hash::make($password),
        ])->assignRole($request['role']);

        DB::table('user_parent')->insert([
            'user_id' => $user->id,
            'parent_id' => $request->parentId,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // event(new Registered($user));

        // Auth::login($user);

        return response()->noContent();
    }

    public function secondryChildren($id)
    {
        $users = DB::select("
        WITH RECURSIVE user_tree AS (
        SELECT u.id, u.name, u.secondary_parent_id, u.phone_number, u.email
        FROM users u
        WHERE u.secondary_parent_id = :userId
        UNION
        SELECT u2.id, u2.name, u2.secondary_parent_id, u2.phone_number, u2.email
        FROM user_tree
        INNER JOIN users as u2 ON user_tree.id = u2.secondary_parent_id
        )
        SELECT *
        FROM user_tree ut
        ", ['userId' => $id]);

        return $users;
    }

    public function secondryParents($id)
    {
        $users = DB::select("
        WITH RECURSIVE user_tree AS (
        SELECT u.id, u0.secondary_parent_id as user_parent, u0.name as parent_name, u0.email as parent_email, u0.phone_number as parent_phone, u0.upi_id, u0.id_type, u0.primary_activated, u0.secondary_activated
        FROM users u
        INNER JOIN users as u0 ON u.secondary_parent_id = u0.id
        WHERE u.id = :userId
        UNION
        SELECT u2.id, u2.secondary_parent_id, u2.name, u2.phone_number, u2.email, u2.upi_id, u2.id_type, u2.primary_activated, u2.secondary_activated
        FROM user_tree
        INNER JOIN users as u2 ON user_tree.user_parent = u2.id
        )
        SELECT *
        FROM user_tree ut
        ", ['userId' => $id]);

        return $users;
    }

    public function approveDonation(Request $request, string $id)
    {
        $donation = Donation::where(['id' => $id, 'approved' => 0])->update(['approved' => $request['status'], 'updated_by' => auth()->user()->id]);
        return $donation;
    }

    public function logins(Request $request)
    {
        $data = DB::table('logins')
            ->join('users', 'users.id', '=', 'logins.user_id')
            ->whereBetween('logins.created_at', [$request['from'] ?? Carbon::yesterday()->subDay(), $request['to'] ?? Carbon::today()])
            ->select('logins.*', 'users.name', 'users.phone_number')
            ->latest('logins.created_at')
            ->paginate(100);

        return $data;
    }

    public function permissions()
    {
        return Permission::all();
    }

    public function userPermissions($id = null)
    {
        $user = User::find($id ?? auth()->user()->id);
        $permissions = $user->getAllPermissions();
        return $permissions;
    }

    public function assignPermissions(Request $request, $id)
    {
        $request->validate([
            'permissions' => 'required',
        ]);
        $user = User::find($id);
        $user->givePermissionTo($request['permissions']);
        return response()->json(['message' => "Permission Assigned"]);
    }

    public function subscriptions($id = null)
    {
        if (is_null($id)) {
            $data = DB::table('subscriptions')
                ->join('users', 'users.id', '=', 'subscriptions.user_id')
                ->join('plans', 'plans.id', 'subscriptions.plan_id')
                ->select('users.name as user_name', 'plans.name as plan_name', 'subscriptions.*', 'users.health_points')
                ->get();

            // $data = DB::table('point_distribution')
            // ->where('point_distribution.beneficiary_id', $id)
            // ->join('users', 'users.id', '=', 'point_distribution.user_id')
            // ->join('users as beneficiary', 'beneficiary.id', '=', 'point_distribution.beneficiary_id')
            // ->join('plans', 'plans.id', '=', 'point_distribution.plan_id')
            // ->select('users.name as user_name', 'users.phone_number as user_phone', 'users.parent_id as parent_id', 'beneficiary.name as beneficiary_name', 'beneficiary.phone_number as beneficiary_phone', 'plans.name', 'point_distribution.*')
            // ->get();
        } else {

                $data = DB::table('subscriptions')
                ->where('subscriptions.user_id', $id)
                ->join('users', 'users.id', '=', 'subscriptions.user_id')
                ->join('plans', 'plans.id', 'subscriptions.plan_id')
                ->select('users.name as user_name', 'plans.name as plan_name', 'subscriptions.*', 'users.health_points')
                ->get();
        }

        return $data;
    }

    public function deleteAttachment($id)
    {
        $data = Storage::delete($id);
        return $data;
    }

    public function rules(Request $request)
    {
        $request->validate([
            'target_amount' => 'required',
            'virolife_donation' => 'required',
            'camapignType' => 'required',
            'round' => 'required',
            'campaignCount' => 'required',
            'campaignAmount' => 'required',
            'juniorDonation1' => 'required',
            'juniorDonation2' => 'required',
            'message1' => 'required',
            'message2' => 'required'
        ]);
        DB::table('rules')->create([
            'target_amount' => $request['amount'],
            'virolife_donation' => $request['donation'],
            'camapign_type' => $request['campaignType'],
            'campaign_count' => $request['campaignCount'],
            'campaign_amount' => $request['campaignAmount'],
            'junior_donation1' => $request['juniorDonation1'],
            'junior_donation2' => $request['juniorDonation2'],
            'message' => $request['message1'],
            'message2' => $request['message2'],
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    public function getVirolifeDonation(Request $request, $id = null)
    {
        $request->validate([
            'purpose' => 'required'
        ]);
        if (is_null($id)) {
            $data = DB::table('virolife_donation')
                ->where(['virolife_donation.purpose' => $request['purpose']])
                // ->where('virolife_donation.user_id', $id)
                ->join('users', 'users.id', '=', 'virolife_donation.user_id')
                ->select('users.name', 'users.phone_number', 'users.id as user_id', 'virolife_donation.*')
                ->orderByDesc('created_at')
                ->get();
        } else {
            $data = DB::table('virolife_donation')
                ->where(['virolife_donation.purpose' => $request['purpose'], 'virolife_donation.user_id' => $id])
                ->join('users', 'users.id', '=', 'virolife_donation.user_id')
                ->select('users.name', 'users.phone_number', 'users.id as user_id', 'virolife_donation.*')
                ->orderByDesc('created_at')
                ->get();
        }

        return $data;
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:' . User::class],
            'role' => ['required', 'exists:roles,name'],
            'phone' => ['required', 'numeric']
            // 'password' => ['required', Rules\Password::defaults()],
            // 'code' => ['exists:users,id']
        ]);

        if ($request->has('code')) {
            if (DB::table('users')->where('parent_id', $request->code)->count() >= 4) {
                return response("Enter another code.", 400);
            }
        }
        $password = $request['password'];
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone_number' => $request->phone,
            'active' => 0,
            'parent_id' => $request->code ?? null,
            'password' => Hash::make($password),
        ])->assignRole($request['role']);

        DB::table('user_parent')->insert([
            'user_id' => $user->id,
            'parent_id' => auth()->user()->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // event(new Registered($user));

        // Auth::login($user);

        return response()->noContent();
    }

    public function getAgents(Request $request, $id = null)
    {
        $from = $request['from'] ?? Carbon::now()->startOfWeek();
        $to = $request['to'] ?? Carbon::now()->endOfWeek();
        $plan_id = $request['planId'];

        if (!empty($plan_id) || !is_null($plan_id)) {
            $data = DB::table('subscriptions')
            ->join('users', 'users.id', '=', 'subscriptions.user_id')
            ->join('plans', 'plans.id', 'subscriptions.plan_id')
            ->where('subscriptions.plan_id', $plan_id)
            ->whereBetween('subscriptions.created_at', [$from, $to])
            ->select('users.name as user_name', 'plans.name as plan_name', 'subscriptions.*', 'users.health_points')
            ->get();

            return $data;
        }

        if (is_null($id)) {
            $data = DB::table('subscriptions')
                ->join('users', 'users.id', '=', 'subscriptions.user_id')
                ->join('plans', 'plans.id', 'subscriptions.plan_id')
                ->whereBetween('subscriptions.created_at', [$from, $to])
                ->select('users.name as user_name', 'plans.name as plan_name', 'subscriptions.*', 'users.health_points')
                ->get();

            // $data = DB::table('point_distribution')
            // ->where('point_distribution.beneficiary_id', $id)
            // ->join('users', 'users.id', '=', 'point_distribution.user_id')
            // ->join('users as beneficiary', 'beneficiary.id', '=', 'point_distribution.beneficiary_id')
            // ->join('plans', 'plans.id', '=', 'point_distribution.plan_id')
            // ->select('users.name as user_name', 'users.phone_number as user_phone', 'users.parent_id as parent_id', 'beneficiary.name as beneficiary_name', 'beneficiary.phone_number as beneficiary_phone', 'plans.name', 'point_distribution.*')
            // ->get();
        } else {
            $data = DB::table('point_distribution')
                ->where('point_distribution.beneficiary_id', $id)
                ->join('users', 'users.id', '=', 'point_distribution.user_id')
                ->join('users as beneficiary', 'beneficiary.id', '=', 'point_distribution.beneficiary_id')
                ->join('plans', 'plans.id', '=', 'point_distribution.plan_id')
                ->whereBetween('point_distribution.created_at', [$from, $to])
                ->select('users.name as user_name', 'users.phone_number as user_phone', 'beneficiary.name as beneficiary_name', 'beneficiary.phone_number as beneficiary_phone', 'plans.name', 'point_distribution.*', 'users.health_points')
                ->get();
        }

        return $data;
    }
    
    public function approvePoints(Request $request)
    {
        $request->validate([
            'transactionId' => 'required|exists:point_distribution,transaction_id',
            'approved' => 'required|boolean'
        ]);

        return DB::table('point_distribution')->where('transaction_id', $request->transaction_id)->update(['approved' => $request->approved]);
    }
    
    // public function pendingRequests($status = null)
    // {
    //     return DB::table('transfer_requests')
    //     ->join('users', 'users.id', '=', 'transfer_requests.user_id')
    //     ->select('transfer_requests.*', 'users.name as user_name')
    //     ->get();
    // }
    
    public function pendingRequests($status)
    {
        if ($status == 'all') {
            $data =  DB::table('transfer_requests')
                ->join('users', 'users.id', '=', 'transfer_requests.user_id')
                ->join('users as receivers', 'receivers.id', '=', 'transfer_requests.receiver_id')
                ->where('transfer_requests.status', '!=', 'pending')
                ->where('transfer_requests.entity', 'points')
                ->select('transfer_requests.*', 'users.name as user_name', 'receivers.name as receiver_name')
                ->get();
            return $data;
        } else {
        $data = DB::table('transfer_requests')
            ->join('users', 'users.id', '=', 'transfer_requests.user_id')
            ->join('users as receivers', 'receivers.id', '=', 'transfer_requests.receiver_id')
            ->where('transfer_requests.status', $status)
            ->where('transfer_requests.entity', 'points')
            ->select('transfer_requests.*', 'users.name as user_name', 'receivers.name as receiver_name')
            ->get();
        return $data;
            
        }
    }
    
        public function pendingWithdrawalRequests($status)
    {
        if ($status == 'all') {
            $data =  DB::table('transfer_requests')
                ->join('users', 'users.id', '=', 'transfer_requests.user_id')
                ->join('users as receivers', 'receivers.id', '=', 'transfer_requests.receiver_id')
                ->where('transfer_requests.status', '!=', 'pending')
                ->where('transfer_requests.entity', 'campaign')
                ->select('transfer_requests.*', 'users.name as user_name', 'receivers.name as receiver_name')
                ->get();
            return $data;
        } else {
        $data = DB::table('transfer_requests')
            ->join('users', 'users.id', '=', 'transfer_requests.user_id')
            ->join('users as receivers', 'receivers.id', '=', 'transfer_requests.receiver_id')
            ->where('transfer_requests.status', $status)
            ->where('transfer_requests.entity', 'campaign')
            ->select('transfer_requests.*', 'users.name as user_name', 'receivers.name as receiver_name')
            ->get();
        return $data;
            
        }
    }
    
    public function approveWithdrawal(Request $request, $id)
    {
        $request->validate([
            'status' => 'required'
        ]);

        $data = DB::transaction(function () use ($request, $id) {
            $data_r = DB::table('transfer_requests')->where(['id' => $id, 'status' => 'pending', 'entity' => 'campaign'])->first();
            if (!empty($data_r) || !is_null($data_r)) {
                $bool = DB::table('transfer_requests')->where(['id' => $id, 'status' => 'pending', 'entity' => 'campaign'])->update(['status' => $request->status]);
            }
            if ($request->status == 'approved') {
                if (!empty($data_r) || !is_null($data_r)) {
                    $req_points = $data_r->value;
                    $user_points = DB::table('point_distribution')->where(['beneficiary_id' => $data_r->user_id])->whereBetween('created_at', [Carbon::today(), Carbon::today()->addYears(5)])->sum('points');
                    if ($user_points - $req_points < 0) {
                        return response("User does not have enough points", 400);
                    }
                }
                if ($bool) {
                    $data = DB::table('transfer_requests')->where('id', $id)->first();
                    $user = User::find($data->receiver_id);
                    $bool = DB::table('withdrawal_requests')->insert([
                        'user_id' => $data->receiver_id,
                        'campaign_id' => $data->transferrable_id,
                        'amount' => $data->value,
                        'opening_balance' => $user->wallet,
                        'closing_balance' => $user->wallet + $data->value,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    $user->update([
                        'wallet' => $user->wallet + $data->value
                    ]);
                }
            }
            return $bool;
        });

        return $data;
    }
    
    public function virolifeDonation(Request $request)
    {
        $request->validate([
            'purpose' => 'required'
            ]);
        $from = $request['from'] ?? Carbon::now()->startOfMonth();
        $to = $request['to'] ?? Carbon::now()->endOfMonth();
        $data = DB::table('virolife_donation')
            ->join('users', 'users.id', '=', 'virolife_donation.user_id')
            ->where('virolife_donation.purpose', $request['purpose'])
            ->whereBetween('virolife_donation.created_at', [$from, $to])
            ->select('virolife_donation.*', 'users.name as user_name')
            ->get();
            // ->groupBy('user_id');

        return $data;
    }

    public function agentCommission(Request $request, $role, $id = null)
    {
        if(!is_null($request['userId'])||!empty($request['userId']))
        {
             $data = DB::table('plan_commission')
                ->where(['plan_commission.user_id'=> $request['userId'], 'roles.name' => $role])
                ->join('users', 'users.id', '=', 'plan_commission.user_id')
                ->join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->join('plans', 'plans.id', '=', 'plan_commission.plan_id')
                ->join('users as subscriber', 'subscriber.id', '=', 'plan_commission.subscriber_id')
                ->select('plan_commission.*', 'users.name as user_name', 'plans.name as plan_name', 'roles.name as role_name', 'subscriber.name as subscriber_name')
                ->get();
        } else {

            $data = DB::table('plan_commission')
                ->where(['roles.name' => $role])
                ->join('users', 'users.id', '=', 'plan_commission.user_id')
                ->join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->join('plans', 'plans.id', '=', 'plan_commission.plan_id')
                ->join('users as subscriber', 'subscriber.id', '=', 'plan_commission.subscriber_id')
                ->select('plan_commission.*', 'users.name as user_name', 'plans.name as plan_name','roles.name as role_name', 'subscriber.name as subscriber_name')
                ->get();

}
        return $data;
    }
    
    public function referralInfo($id = null)
    {
        if(!is_null($id)){
        $data = DB::table('point_distribution')
                ->where(['point_distribution.beneficiary_id'=> $id, 'point_distribution.purpose' => 'referrals'])
                ->join('users', 'users.id', '=', 'point_distribution.user_id')
                ->join('users as beneficiary', 'beneficiary.id', '=', 'point_distribution.beneficiary_id')
                ->join('plans', 'plans.id', '=', 'point_distribution.plan_id')
                ->select('users.name as user_name', 'users.phone_number as user_phone', 'beneficiary.name as beneficiary_name', 'beneficiary.phone_number as beneficiary_phone', 'plans.name as plan_name', 'point_distribution.*', 'point_distribution.points as health_points')
                ->get();
                
        return $data;
        } else {
                $data = DB::table('point_distribution')
                ->where(['point_distribution.purpose' => 'referrals'])
                ->join('users', 'users.id', '=', 'point_distribution.user_id')
                ->join('users as beneficiary', 'beneficiary.id', '=', 'point_distribution.beneficiary_id')
                ->join('plans', 'plans.id', '=', 'point_distribution.plan_id')
                ->select('users.name as user_name', 'users.phone_number as user_phone', 'beneficiary.name as beneficiary_name', 'beneficiary.phone_number as beneficiary_phone', 'plans.name as plan_name', 'point_distribution.*', 'point_distribution.points as health_points')
                ->get();
                
                return $data;
        }
    }
    
    public function videoViews()
    {
    return $videoWatchCounts = DB::table('user_video')
    ->select('user_video.video_id', 'videos.title', 'videos.provider', 'videos.link', DB::raw('count(*) as watch_count'))
    ->join('videos', 'videos.id', '=', 'user_video.video_id')
    ->groupBy('user_video.video_id', 'videos.title', 'videos.provider', 'videos.link')
    ->get();
    }
    
    public function videoHistory()
    {
    return DB::table('user_video')
    ->join('videos', 'user_video.video_id', '=', 'videos.id')
    ->join('users', 'user_video.user_id', '=', 'users.id')
    ->select('videos.title', 'users.name', 'videos.id as video_id', 'users.id as user_unique', 'videos.provider', DB::raw('count(*) as watch_count'))
    ->groupBy('videos.title', 'users.name', 'video_id', 'user_unique', 'videos.provider')
    ->get();
    }
    
    public function printReports(Request $request, $report)
    {
        $request->validate([
            'extension' => 'required'
            ]);
        switch ($report) {
            case 'users':
                $request->validate([
                    'role' => 'required'
                    ]);
                return Excel::download(new UserExport($request['role']), "users.{$request['extension']}");
                break;

            case 'subscription':
                return Excel::download(new SubscriptionExport($request['userId']), "subscription.{$request['extension']}");
                break;

            case 'transfer-request':
                $request->validate([
                    'status' => 'required'
                ]);
                return Excel::download(new TransferRequest($request['status']), "transferrequests.{$request['extension']}");
                break;

            case 'withdrawal-request':
                $request->validate([
                    'status' => 'required'
                ]);
                return Excel::download(new WithdrawalRequest($request['status']), "withdrawalrequests.{$request['extension']}");
                break;

            case 'all-team-donation':
                $request->validate([
                    'purpose' => 'required|exists:virolife_donation,purpose'
                ]);
                return Excel::download(new AllTeamDonationExport($request['userId'], $request['purpose']), "donation.{$request['extension']}");
                break;
                
            case 'logins':
                return Excel::download(new LoginExport(), "logins.{$request['extension']}");
                break;
            
            case 'payouts':
                return Excel::download(new PayoutExport(), "payouts.{$request['extension']}");
                break;
                
            case 'referral':
                return Excel::download(new ReferralExport(), "referrals.{$request['extension']}");
                break;

            default:
                return Excel::download(new UserExport($request['id']), "users.{$request['extension']}");
                break;
        }
    }
    
    public function allTeamDonation()
    {
            return DB::table('virolife_donation')
            ->where('purpose', 'all-team')
            ->join('users', 'users.id', '=', 'virolife_donation.user_id')
            ->select('user_id', 'users.name', 'users.stars', 'users.created_at', DB::raw('SUM(amount) as amount'), DB::raw('stars/((DATEDIFF(CURDATE() ,users.created_at)*0.032855)) as performance'))
            ->groupBy('user_id', 'users.name', 'users.stars', 'users.created_at')
            ->get();
    }
    
    public function approveCommission(Request $request, $id)
    {
        $request->validate([
            'status' => 'required',
        ]);

        $data = DB::table('commission_requests')->where(['id' => $id, 'status' => 'pending'])->first();
        if (!$data) {
            return response("No data was found.", 404);
        }

        if ($request['status'] == 'approved') {
            $user = User::find($data->user_id);
            $closing_balance = $user->wallet - $data->request_amount;
            $data = DB::table('commission_requests')->where(['id' => $id, 'status' => 'pending'])
                ->update([
                    'status' => 'approved',
                    'admin_id' => auth()->user()->id,
                    'closing_amount' => $closing_balance,
                    'updated_at' => now()
                ]);
            $user->update([
                'wallet' => $closing_balance
            ]);

            return $data;
        } else {
            $user = User::find($data->user_id);
            $closing_balance = $user->wallet;
            $data = DB::table('commission_requests')->where(['id' => $id, 'status' => 'pending'])
                ->update([
                    'status' => 'rejected',
                    'admin_id' => auth()->user()->id,
                    'admin_request' => $request['remarks'],
                    'closing_amount' => $closing_balance,
                    'updated_at' => now()
                ]);
        }

        return $data;
    }
    
    public function getCommission()
    {
        return DB::table('commission_requests')
        // ->where(['user_id' => auth()->user()->id])
        ->join('users', 'users.id', '=', 'commission_requests.user_id')
        ->select('commission_requests.*', 'users.name')
        ->get();
    }
    
    public function allPayouts($role)
    {
        $data = DB::table('users')
        ->join('commission_requests', 'commission_requests.user_id', '=', 'users.id', 'left')
        ->join('user_parent', 'user_parent.user_id', 'users.id')
        ->join('users as parent', 'parent.id', 'user_parent.parent_id')
        ->join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
        ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
        ->where('roles.name', $role)
        ->where(function($q){
        $q->where('status', 'approved')
            ->orWhere('status', null);
        })
        ->select('users.name', 'users.id as users_id', 'users.created_at as register_at', 'users.wallet', 'users.email', 'users.phone_number', 'users.active', 'commission_requests.id', 'commission_requests.user_id', 'commission_requests.request_amount', 'commission_requests.status', 'roles.name as role', 'parent.name as parent_name', 'parent.id as parent_id')
                // ->groupBy('users.name', 'commission_requests.id', 'commission_requests.user_id', 'commission_requests.request_amount', 'commission_requests.status', 'users_id')
        ->get()
        ->groupBy('users_id')->map(function($q) {
            return ['payout' => $q->sum('request_amount'), 'name' => $q[0]->name, 'wallet' => $q[0]->wallet, 'created_at' => $q[0]->register_at, 'role' => $q[0]->role, 'email' => $q[0]->email, 'phone_number' => $q[0]->phone_number, 'active' => $q[0]->active, 'parent_id' => $q[0]->parent_id, 'parent_name' => $q[0]->parent_name];
        });
        
        return $data;
    }
    
    public function editGift(Request $request, string $id)
    {
        $gift = Gift::where(['id' => $id, 'redeemed' => 0])->first();
        if($gift)
        {
            return Gift::where('id', $id)->update([
                'user_id' => $request['userId'],
                'agent_id' => $request['agentId'],
                'distributor_id' => $request['distributorId']
                ]);
        } else {
            return response("No gift card found.", 404);
        }
    }
    
    public function donationData($group){
    $sum =  User::role('users')
        ->leftJoin('donations', function($join) use ($group) {
            $join->on('donations.donated_to', '=', 'users.id');
            $join->where('donations.group', '=', $group);
        })
        ->select('users.id', DB::raw('sum(amount) as primary_sum'))
        ->groupBy('users.id')
        ->get();
        
    return $sum;
        
    }
    
    public function razorpayTransaction($purpose)
    {
        if ($purpose == 'all') {
            return DB::table('gateway_payments')
            ->join('users', 'users.id', '=', 'gateway_payments.user_id')
            ->select('gateway_payments.*', 'users.name')
            ->get();
        } else {
            return DB::table('gateway_payments')
            ->where('gateway_payments.purpose', $purpose)
            ->join('users', 'users.id', '=', 'gateway_payments.user_id')
            ->select('gateway_payments.*', 'users.name')
            ->get();
        }
    }
    
    public function campaignDonations()
    {
        return DB::table('campaign_donations')
            // ->where('campaign_donation.user_id', auth()->user()->id)
            ->join('campaigns', 'campaigns.id', '=', 'campaign_donations.campaign_id')
            ->join('users', 'users.id', '=', 'campaign_donations.user_id')
            ->select('campaign_donations.*', 'campaigns.title', 'users.name as user_name')
            ->get();
    }
}
