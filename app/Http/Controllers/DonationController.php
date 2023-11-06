<?php

namespace App\Http\Controllers;

use App\Models\Donation;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DonationController extends Controller
{

    public function __construct()
    {
        $this->middleware('id', ['only' => ['store']]);
        $this->middleware('activate', ['only' => ['store']]);
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = Donation::with('donatable')->get();
        return $data;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric',
            'donatable_id' => 'required|exists:users,id',
        ]);

        $beneficiary = User::find($request['donatable_id']);

        $donation = Donation::create([
            'user_id' => auth()->user()->id,
            'amount' => $request['amount'],
            'updated_by' => auth()->user()->id,
            'reciever_round' => $beneficiary->round,
            'sender_round' => auth()->user()->round,
            'group' => $request['group'],
            'donatable_type' => "App\Models\User",
            'donated_to' => $request['donatable_id'],
            'donatable_id' => $request['donatable_id'],
            'remarks' => $request->remarks ?? null
        ]);

        // DB::table('user_donation')->insert([
        //     'user_id' => auth()->user()->id,
        //     'donatable_id' => $request['donatable_id'],
        //     'created_at' => now(),
        //     'updated_at' => now()
        // ]);

        return $donation;
    }

    public function donateAdmin(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric',
            'donatable_id' => 'required',
        ]);


        $beneficiary = User::find($request['donatable_id'] ?? 146);

        $donation = Donation::create([
            'user_id' => auth()->user()->id,
            'updated_by' => auth()->user()->id,
            'amount' => $request['amount'],
            'donatable_type' => "App\Models\User",
            'donated_to' => $request['donatable_id'],
            'group'=>$request['group'],
            'donatable_id' => $request['donatable_id'],
            'reciever_round' => $beneficiary->round,
            'donated_to_admin' => true,
            'sender_round' => auth()->user()->round,
            'remarks' => $request->remarks ?? null
        ]);

        // DB::table('user_donation')->insert([
        //     'user_id' => auth()->user()->id,
        //     'donatable_id' => $request['donatable_id'],
        //     'created_at' => now(),
        //     'updated_at' => now()
        // ]);

        return $donation;
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $donation = Donation::with('donatable')->where('id', $id)->get();
        return $donation;
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
        $delete = Donation::where(['id' => $id, 'donatable_id' => auth()->user()->id])->delete();
        return $delete;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function adminDestroy(string $id)
    {
        $delete = Donation::where(['id' => $id])->delete();
        return $delete;
    }

    public function authUserCollections()
    {
        $donations = DB::table('user_donation')->where('donatable_id', auth()->user()->id)
            ->join('users', 'users.id', '=', 'user_donation.user_id')
            ->select('users.name', 'users.phone_number', 'users.id', 'user_donation.*')
            ->get();
        // Donation::with('user')->where(['donatable_id' => auth()->user()->id, 'donatable_type' => 'App\Models\User'])->get();
        return $donations;
    }

    public function userCollections()
    {
        $donations = Donation::with(['user', 'updatedUser'])->where(['donatable_id' => auth()->user()->id, 'donatable_type' => 'App\Models\User'])->get();
        return $donations;
    }

    public function authUserDonations()
    {
        $donations = DB::table('donations')
            ->where('user_id', auth()->user()->id)
            ->where('deleted_at', null)
            ->join('users', 'users.id', '=', 'donations.user_id')
            ->select('users.name', 'users.phone_number', 'users.id', 'donations.*')
            ->get();
        // Donation::with('user')->where(['user_id' => auth()->user()->id, 'donatable_type' => 'App\Models\User'])->get();
        return $donations;
    }

    public function approveDonation(Request $request, string $id)
    {
        $donation = Donation::where(['id' => $id, 'donatable_id' => auth()->user()->id])->update(['approved' => $request['status'], 'updated_by' => auth()->user()->id]);
        $data = Donation::where(['id' => $id, 'donatable_id' => auth()->user()->id])->first();
        if ($request['status'] == 1) {
            DB::table('users')->where('id', auth()->user()->id)->update([
                'group_collection' => auth()->user()->group_collection + $data->amount,
                'updated_at' => now()
            ]);
            
            DB::table('user_donation')->insert([
            'user_id' => $data->user_id,
            'donatable_id' => $data->donatable_id,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        }
        return $donation;
    }

    public function adminApproveDonation(Request $request, string $id)
    {
        $donation = Donation::where(['id' => $id])->update(['approved' => $request['status'], 'updated_by' => auth()->user()->id]);
        $data = Donation::where(['id' => $id])->first();
            if ($request['status'] == 1) {
                $user = User::find($data->user_id);
            DB::table('users')->where('id', $data->user_id)->update([
                'group_collection' => $user->group_collection + $data->amount,
                'updated_at' => now()
            ]);
            
            DB::table('user_donation')->insert([
            'user_id' => $data->user_id,
            'donatable_id' => $data->donatable_id,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        }
        return $donation;
    }

    public function userDonations($id = null)
    {
        if (!is_null($id)) {
            $donations = Donation::with(['user', 'updatedUser' => function($q){
            $q->select('id as updatee_id', 'name as updatee_name', 'phone_number as updatee_phone');
        }])->where(['donatable_id' => $id, 'donatable_type' => 'App\Models\User'])->get();
        } else {

            $donations = Donation::with(['user', 'updatedUser' => function($q){
            $q->select('id as updatee_id', 'name as updatee_name', 'phone_number as updatee_phone');
        }])->where(['donatable_type' => 'App\Models\User'])->get();
        }
        return $donations;
    }

    public function rules($round = null)
    {
        if (is_null($round)) {
            $rule = DB::table('rules')->get();
            return $rule;
        }
        $rule = DB::table('rules')->where('round', $round)->get();
        return $rule;
    }

    public function juniorDonation(Request $request)
    {
        $request->validate([
            'beneficiaryId' => 'required|exists:users,id',
            'amount' => 'required'
        ]);
        $reciever = User::find($request['beneficiaryId']);
        DB::table('junior_donations')->insert([
            'sender_id' => auth()->user()->id,
            'reciever_id' => $request['beneficiaryId'],
            'amount' => $request['amount'],
            'sender_round' => auth()->user()->round,
            'reciever_round' => $reciever->round,
            'updated_at' => now(),
            'created_at' => now()
        ]);
    }

    public function getJuniorDonations($user_id, $round)
    {
        $data = DB::table('junior_donations')
            ->where(['sender_id' => $user_id, 'sender_round' => $round])
            ->join('users as sender', 'sender.id', 'junior_donations.sender_id')
            ->join('users as reciever', 'reciever.id', 'junior_donations.reciever_id')
            ->select('sender.name', 'reciever.name as reciever_name', 'junior_donations.*')
            ->get();

        return $data;
    }

    public function getSeniorDonations($user_id, $round)
    {
        $data = Donation::with('user')->where(['user_id' => auth()->user()->id, 'donatable_type' => 'App\Models\User', 'sender_round' => $round])->get();

        return $data;
    }

    public function campaignDonations($round = null)
    {
        if (is_null($round)) {
            $data = Donation::with('donatable')->where(['user_id' => auth()->user()->id, 'donatable_type' => 'App\Models\Camapign'])->get();
        } else {
            $data = Donation::with('donatable')->where(['user_id' => auth()->user()->id, 'donatable_type' => 'App\Models\Camapign', 'sender_round' => $round])->get();
        }

        return $data;
    }

    public function donateVirolife(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric',
            'purpose' => 'required'
        ]);
        $transaction_id = uniqid("VIRTM-DON");
        $user_id = auth()->user()->id;
        DB::table('virolife_donation')->insert([
            'user_id' => $user_id,
            'amount' => $request->amount,
            'purpose' => $request->purpose,
            'transaction_id' => $transaction_id,
            'points' => 200,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $data = DB::table('users')->where('id', $user_id)->update([
            'stars' => auth()->user()->stars + 20,
            // 'health_points' => auth()->user()->virolife_points + 200,
        ]);

        return $data;
    }

    public function getVirolifeUserDonation(Request $request)
    {
        $request->validate([
            'purpose' => 'required'
        ]);

        $data = DB::table('virolife_donation')
            ->where(['virolife_donation.purpose' => $request['purpose'], 'virolife_donation.user_id' => auth()->user()->id])
            ->join('users', 'users.id', '=', 'virolife_donation.user_id')
            ->select('virolife_donation.*', 'users.name', 'users.phone_number', DB::raw('stars/((DATEDIFF(CURDATE() ,users.created_at)*0.032855)) as performance'))
            // ->orderByDesc('created_at')
            ->get();

        return $data;
    }

    public function commission(int $user_id)
    {
        $plan = Subscription::where('user_id', $user_id)->first();
        $data = DB::table('commissions')->where('plan_id', $plan->plan_id)->first();
        if (empty($data)) {
            return response("No plan found.");
        }

        $user = User::findOrFail($user_id);

        $role = $user->getRoleNames()[0];
        $credit = $data->{$role};

        $transaction_id = uniqid("VIR-COMM");
        $opening_balance = auth()->user()->commission;
        $closing_balance = $opening_balance + $credit;
        $metadata = [
            'event' => 'commission_distribute',
            'user_name' => auth()->user()->name,
            'user_id' => auth()->user()->id
        ];
        $parent = DB::table('user_parent')->where('user_id', $user_id);
        if ($parent->exists()) {
            $parent = $parent->first();
            $this->commission($parent->parent_id);
        }
        $this->transaction($user_id, null, $transaction_id, 'App\Models\Commission', $data->id, $credit, 0, $opening_balance, $closing_balance, json_encode($metadata));

        return $metadata;
    }

    public function redeem(Request $request)
    {
        $request->validate([
            'code' => 'required|exists:gifts,code|size:6',
            'paymentMethod' => 'required'
        ]);

        if($request['paymentMethod'] == 'gateway')
        {
            $request->validate([
                'transactionId' => 'required',
                'amount' => 'required',
                ]);
            $this->gatewayTransaction($request['transactionId'], $request['amount'], 'campaign-donation');
            return $this->donateVirolife($request);
        }

        $code = $request['code'];

        $data = DB::table('gifts')->where(['code' => $code, 'purpose' => 'all-team-process', 'redeemed' => false])->update([
            'user_id' => auth()->user()->id,
            'redeemed' => true
        ]);

        if ($data == 0) {
            return response("Bad request", 400);
        }

        return $this->donateVirolife($request);
    }
    
    public function donateCampaign(Request $request)
    {
        $request->validate([
            'campaignId' => ['required', 'exists:campaigns,id'],
            'amount' => ['required', 'numeric', 'min:1'],
            'transactionId' => ['required', 'string'],
            'name' => ['required', 'string'],
            'phoneNumber' => ['required', 'digits:10'],
        ]);
        
        $this->gatewayTransaction($request['transactionId'], $request['amount'], 'campaign-donation');

        return DB::table('campaign_donations')
            ->insert([
                'user_id' => auth()->user()->id,
                'campaign_id' => $request->campaignId,
                'transaction_id' => $request->transactionId,
                'amount' => $request->amount,
                'phone_number' => $request->phoneNumber,
                'name' => $request->name,
                'created_at' => now(),
                'updated_at' => now()
            ]);
    }
    
    public function myCampaignDonations()
    {
        return DB::table('campaign_donations')
        ->where('campaign_donations.user_id', auth()->user()->id)
        ->join('campaigns', 'campaigns.id', '=', 'campaign_donations.campaign_id')
        ->select('campaign_donations.*', 'campaigns.title')
        ->get();
    }
}
