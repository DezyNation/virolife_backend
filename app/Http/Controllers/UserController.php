<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Gift;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = DB::table('users')
            ->join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->select()
            ->select(
                'users.id',
                'users.first_name',
                'users.middle_name',
                'users.last_name',
                'users.name',
                'users.active',
                'users.address',
                'users.email',
                'users.phone_number',
                'users.account_number',
                'users.ifsc',
                'users.upi_id',
                'users.bank_name',
                'users.wallet',
                'users.dob',
                'users.gender',
                'users.profile',
                'users.parent_id',
                'users.secondary_parent_id',
                'users.created_at',
                'roles.name as role_name'
            )->get();
        return $data;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $data = DB::table('users')->where('users.id', $id)->join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->select()
            ->select(
                'users.id',
                'users.first_name',
                'users.middle_name',
                'users.last_name',
                'users.name',
                'users.active',
                'users.address',
                'users.email',
                'users.phone_number',
                'users.account_number',
                'users.ifsc',
                'users.upi_id',
                'users.bank_name',
                'users.wallet',
                'users.dob',
                'users.gender',
                'users.profile',
                'users.parent_id',
                'users.secondary_parent_id',
                'users.created_at',
                'roles.name as role_name'
            )->get();
        return $data;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = auth()->user();

        if ($request->hasFile('attachment1')) {
            $attachment1 = $request->file('attachment1')->store('attachment1');
        }
        if ($request->hasFile('attachment2')) {
            $attachment2 = $request->file('attachment2')->store('attachment2');
        }

        User::where('id', auth()->user()->id)->update([
            'first_name' => $request->firstName ?? $user->first_name,
            'middle_name' => $request->middleName ?? $user->middle_name,
            'last_name' => $request->lastName ?? $user->last_name,
            'email' => $request->email ?? $user->email,
            'address' => json_encode($request->address) ?? $user->address,
            'gender' => $request->gender ?? $user->gender,
            'phone_number' => $request->phone_number ?? $user->phone_number,
            'dob' => $request->dob ?? $user->dob,
            'attachment_1' => $attachment1 ?? $user->attachment_1,
            'attachment_2' => $attachment2 ?? $user->attachment_2,
            'account_number' => $request->accountNumber ?? $user->account_number,
            'ifsc' => $request->ifsc ?? $user->ifsc,
            'upi_id' => $request->upi ?? $user->upi_id,
            'bank_name' => $request->bankName ?? $user->bank_name,
        ]);
        return auth()->user();
    }

    public function updateUser(Request $request, string $id)
    {
        $user = User::find($id);

        if ($request->hasFile('attachment1')) {
            $attachment1 = $request->file('attachment1')->store('attachment1');
        }
        if ($request->hasFile('attachment2')) {
            $attachment2 = $request->file('attachment2')->store('attachment2');
        }

        User::where('id', $id)->update([
            'first_name' => $request->firstName ?? $user->first_name,
            'middle_name' => $request->middleName ?? $user->middle_name,
            'last_name' => $request->lastName ?? $user->last_name,
            'wallet' => $request->wallet ?? $user->wallet,
            'email' => $request->email ?? $user->email,
            'address' => $request->address ?? $user->address,
            'gender' => $request->gender ?? $user->gender,
            'phone_number' => $request->phoneNumber ?? $user->phone_number,
            'dob' => $request->dob ?? $user->dob,
            'active' => $request->active ?? $user->active,
            'account_number' => $request->accountNumber ?? $user->account_number,
            'attachment_1' => $attachment1 ?? $user->attachment_1,
            'attachment_2' => $attachment2 ?? $user->attachment_2,
            'ifsc' => $request->ifsc ?? $user->ifsc,
            'upi_id' => $request->upi ?? $user->upi_id,
            'bank_name' => $request->bankName ?? $user->bank_name,
        ]);
        return $user;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
    }

    public function donations()
    {
        $data = User::with('donations')->where('id', auth()->user()->id)->get();
        return $data;
    }

    public function adminDonations($id)
    {
        $data = User::with('donations')->where('id', $id)->get();
        return $data;
    }

    public function findUser(Request $request)
    {
        $search = $request['search'];

        $user = User::where('email', 'like', '%' . $search . '%')->orWhere('name', 'like', '%' . $search . '%')->orWhere('phone_number', 'like', '%' . $search . '%')->get();
        return $user;
    }
    
    public function myViroPoints()
    {
        $points = DB::table('virolife_donation')
        ->where('user_id', auth()->user()->id)
        ->whereBetween('created_at', [Carbon::today()->subMonth(), Carbon::tomorrow()])
        // ->where('expiry_at', '>', Carbon::today())->̛where('expiry_at', '<', Carbon::today()->subMonth())
            ->sum('points');

        return $points;
    }

    public function myAdmins()
    {
        $groups = User::with(['groupMembership'])->select('users.*')->where(['id' => auth()->user()->id])->get();
        return $groups;
    }

    public function userAdmins(string $id)
    {
        $groups = User::with(['groupMembership'])->select('users.*')->where(['id' => $id])->get();
        return $groups;
    }

    public function userList($role)
    {
        $users = DB::table('users')
        ->join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
        ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
        ->where('roles.name', '=', $role)
        ->join('donations', 'donations.donated_to', '=', 'users.id', 'left')
        ->join('point_distribution', 'point_distribution.beneficiary_id', '=', 'users.id', 'left')
        ->select('users.id', 'users.name', 'users.email', 'users.phone_number', 'users.dob', 'users.stars', 'users.ad_points', 'users.created_at', 'users.active',DB::raw('stars/(
            (YEAR(NOW()) - YEAR(users.created_at)*12) + (MONTH(NOW()) - MONTH(users.created_at)+ 1)
            ) as performance'),
        )
        ->groupBy('users.id', 'users.name', 'users.email', 'users.phone_number', 'users.dob', 'users.stars', 'users.ad_points', 'users.created_at')
        ->get();
        
        $points =  DB::table('users')
        ->join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
        ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
        ->where('roles.name', '=', $role)
        ->leftJoin('point_distribution', function($join) {
            $join->on('point_distribution.beneficiary_id', '=', 'users.id');
            $join->where('expiry_at', '>', Carbon::now());
        })
        ->select('users.id', DB::raw('sum(point_distribution.points) as points'))
        ->groupBy('users.id')
        ->get();
        
        $primary_sum =  DB::table('users')
        ->join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
        ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
        ->where('roles.name', '=', $role)
        ->leftJoin('donations', function($join) {
            $join->on('donations.donated_to', '=', 'users.id');
            $join->where('donations.group', '=', 'primary');
        })
        ->select('users.id', DB::raw('sum(amount) as primary_sum'))
        ->groupBy('users.id')
        ->get();
        
        $secondary_sum =  DB::table('users')
        ->join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
        ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
        ->where('roles.name', '=', $role)
        ->leftJoin('donations', function($join) {
            $join->on('donations.donated_to', '=', 'users.id');
            $join->where('donations.group', '=', 'secondary');
        })
        ->select('users.id', DB::raw('sum(amount) as secondary_sum'))
        ->groupBy('users.id')
        ->get();
        
    $result = $users
    ->concat($points)
    ->concat($primary_sum)
    ->concat($secondary_sum)
    ->groupBy('id')
    ->map(function ($items) {
        return $items->reduce(function ($merged, $item) {
            return array_merge($merged, (array) $item);
        }, []);
    })
    ->values();
    
    return $result;
    }

    public function updateRound(Request $request)
    {
        User::where('id', auth()->user()->id)->update([
            'round' => $request['round']
        ]);
    }

    public function myAgents($user_id = null)
    {
        $data = DB::table('user_parent')
            ->where('user_parent.parent_id', $user_id ?? auth()->user()->id)
            ->join('users as parents', 'parents.id', '=', 'user_parent.parent_id')
            ->join('users as children', 'children.id', '=', 'user_parent.user_id')
            ->select('parents.name as parent_name', 'children.name as child_name', 'user_parent.parent_id', 'children.id', 'children.phone_number', 'children.email', 'children.health_points', 'children.ad_points', 'children.virolife_points', 'children.created_at', 'children.dob', 'children.commission')
            ->get()
            ->groupBy('parent_id');
            

        return $data;
    }
    
    public function resetPasswordLink(Request $request)
    {
        $request->validate([
            'email' => 'email|required|exists:users,email'
        ]);

        $user = User::where('email', $request['email'])->first();

        $token = Str::uuid();
        DB::table('password_reset_tokens')->updateOrInsert(
            [
                'email' => $request['email']
            ],
            [
                'token' => $token,
                'created_at' => now()
            ]
        );

        Mail::raw("This is password reset link https://virolife.in/forgot-password?reset-token=$token", function ($message) use ($request, $user) {
            $message->from('info@virolife.in', 'Team Virolife');
            $message->sender('info@virolife.in', 'Team Virolife');
            $message->to($request['email'], $user->name);
            // $message->replyTo('john@johndoe.com', 'John Doe');
            $message->subject('Password reset link');
            $message->priority(1);
            // $message->attach('pathToFile');
        });

        return response()->json(['message' => "Password reset link sent to your email."]);
    }

    public function resetPass(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'token' => 'required|uuid|exists:password_reset_tokens,token',
            'password' => 'required|confirmed'
        ]);

        $data = DB::table('password_reset_tokens')->where(['token' => $request['token'], 'email' => $request['email']])->first();

        if (empty($data)) {
            return response("Wrong token passed.", 404);
        }

        $endtime = Carbon::now();
        $startime = $data->created_at;
        $difference = $endtime->diffInMinutes($startime);
        if ($difference > 30) {
            return response("Token expired", 403);
        }

        User::where('email', $request['email'])->update([
            'password' => Hash::make($request['password'])
        ]);
        
        DB::table('password_reset_tokens')->where(['token' => $request['token'], 'email' => $request['email']])->delete();

        return response("Password reset successfully");

    }
    
    public function myPoints()
    {
        $points = DB::table('point_distribution')->where(['beneficiary_id' => auth()->user()->id])
        // ->where(function ($q) {
        //     $q->where('expiry_at', '>', Carbon::now())
        //         ->orWhere('expiry_at', null);
        // })
        // ->whereBetween('expiry_at', [Carbon::now(), Carbon::now()->addYears(10)])
        ->where('expiry_at', '>', Carbon::now())
            // ->where('approved', '!=', 0)
            ->sum('points');

        return $points;
    }

    public function transferPoints(Request $request, $id)
    {
        $request->validate([
            'status' => 'required'
        ]);
        DB::transaction(function () use ($request, $id) {
        DB::table('transfer_requests')->where(['id' => $id, 'status' => 'pending', 'entity' => 'points'])->update(['status' => $request['status'], 'updated_at' => now()]);
        $data = DB::table('transfer_requests')->where('id', $id)->first();
        if ($request->status == 'approved') {
            
            $data_r = DB::table('transfer_requests')->where(['id' => $id, 'status' => 'pending', 'entity' => 'points'])->first();
            if(!empty($data_r)||!is_null($data_r))
            {
                $req_points=$data_r->value;
                $user_points = DB::table('point_distribution')->where(['beneficiary_id' => $data_r->user_id])->whereBetween('created_at', [Carbon::today(), Carbon::today()->addYears(5)])->sum('points');
                if($user_points-$req_points<0)
                {
                    return response("User does not have enough points", 400);
                }
            }
            
                $transaction_id = uniqid('VIR-PT');
                $debit = DB::table('point_distribution')->insert([
                    'user_id' => $data->user_id,
                    'beneficiary_id' => $data->user_id,
                    'points' => -$data->value,
                    'expiry_at' => Carbon::now()->addYears(10),
                    'transaction_id' => $transaction_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'approved' => 1
                    // 'expiry_at' => Carbon::now()->addMonth()
                ]);
                $credit = DB::table('point_distribution')->insert([
                    'user_id' => $data->user_id,
                    'beneficiary_id' => $data->receiver_id,
                    'points' => $data->value,
                    'transaction_id' => $transaction_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'expiry_at' => Carbon::now()->addMonth(),
                    'approved' => 1
                ]);
}
                return ['credit' => $credit, 'debit' => $debit];
            });
        
    }
    
    public function transferRequest(Request $request)
    {
        $request->validate([
            'points' => 'required|numeric|min:35',
            'beneficiaryId' => 'required|exists:users,id'
        ]);

        return DB::table('transfer_requests')->insert([
            'user_id' => auth()->user()->id,
            'receiver_id' => $request['beneficiaryId'],
            'entity' => 'points',
            'value' => $request['points'],
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    public function transferCampaignRequest(Request $request)
    {
        $request->validate([
            'points' => 'required|numeric|min:35',
            'campaignId' => 'required|exists:campaigns,id'
        ]);

        return DB::table('transfer_requests')->insert([
            'user_id' => auth()->user()->id,
            'receiver_id' => auth()->user()->id,
            'entity' => 'campaign',
            'value' => $request['points'],
            'transferrable_type' => 'App\Models\Campaign',
            'transferrable_id' => $request->campaignId,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
    
    public function myGifts()
    {
        return Gift::where('user_id', auth()->user()->id)->get();
    }
    
    public function myPointRequests()
    {
        $points = DB::table('transfer_requests')->where(['entity'=> 'points', 'user_id' => auth()->user()->id])->get();
        // $withdrawals = DB::table('transfer_requests')->where(['entity'=> 'campaign', 'user_id' => auth()->user()->id])->count();

        return $points;
    }
    
    public function myCampaignRequests()
    {
        $campaign = DB::table('transfer_requests')->where(['entity'=> 'campaign', 'user_id' => auth()->user()->id])->get();
        // $withdrawals = DB::table('transfer_requests')->where(['entity'=> 'campaign', 'user_id' => auth()->user()->id])->count();

        return $campaign;
    }
    
    public function commissionRequest(Request $request)
    {
        
        $request->validate([
            'amount' => 'required|numeric'
        ]);
        
        $user_id = auth()->user()->id;
        $user_commission = auth()->user()->wallet;
        $request_amount = $request['amount'];
        $difference = $user_commission - $request_amount;
        $last_rec = DB::table('commission_requests')->where(['user_id' => $user_id, 'status' => 'pending'])->latest();

        if (empty($last_rec) || is_null($last_rec) || $last_rec->exists()) {
            return response("Another transaction is in process, please try again later.");
        }

        if ($difference <= 0) {
            return response("Not enough balance.");
        }

        $data = DB::table('commission_requests')->insert([
            'user_id' => $user_id,
            'user_remarks' => $request['remarks'],
            'request_amount' => $request['amount'],
            'opening_amount' => $user_commission,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return $data;
    }
    
    public function getCommissionRequest()
    {
        return DB::table('commission_requests')->where(['user_id' => auth()->user()->id])->get();
    }
    
    public function userHealthPoints(Request $request)
    {
        
        $data = DB::table('point_distribution')
            ->join('user_parent', 'user_parent.user_id', '=', 'point_distribution.user_id')
            ->join('users', 'users.id', '=', 'user_parent.user_id')
            ->where('user_parent.parent_id', $request['userId'] ?? auth()->user()->id)
            ->select('point_distribution.*')
            ->where('expiry_at', '>', now())
            ->get()
            ->groupBy(['user_id', 'purpose'])->map(function($item) {
                return $item->map(function ($key) {
                    return $key->sum('points');
                });
            });

        return $data;
    }
    
    public function donationSum()
    {
        $primary = DB::table('donations')->where(['donated_to'=> auth()->user()->id, 'group' => 'primary'])->sum('amount');
        $secondary = DB::table('donations')->where(['donated_to'=> auth()->user()->id, 'group' => 'secondary'])->sum('amount');
        
        return [
            'primary' => $primary,
            'secondary' => $secondary
            ];
    }
    
    public function directJuniors()
    {
        $primary = User::where('parent_id', auth()->user()->id)->get();
        $secondary = User::where('secondary_parent_id', auth()->user()->id)->get();
        
        return ['primary' => $primary, 'secondary' => $secondary];
    }
    
    public function myTransactions()
    {
        return DB::table('gateway_payments')->where('user_id', auth()->user()->id)->get();
    }
    
}
