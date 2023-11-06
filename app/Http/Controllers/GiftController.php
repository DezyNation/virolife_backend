<?php

namespace App\Http\Controllers;

use App\Models\Gift;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GiftController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:agent|distributor', ['only' => ['update']]);
        $this->middleware('role:admin', ['only' => ['index', 'show', 'store']]);
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Gift::all();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        // if($request->count > 1)
        // {
            return $this->bulkGifts($request);
        //     $request->validate([
        //     'userId' => 'required|exists:users,id',
        //     'code' => 'required|numeric',
        //     'purpose' => 'required|string',
        //     'expiry' => 'required'
        // ]);
        // Gift::create([
        //     'user_id' => $request['userId'],
        //     'code' => $request['code'],
        //     'plan_id' => $request['plan'],
        //     'purpose' => $request['purpose'],
        //     'amount' => $request['amount'],
        //     'expiry_at' => $request['expiry']
        // ]);
        
        // }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        if($id == 'all'){
        return Gift::all();
        }
        else {
        return Gift::where('purpose', $id)->get();
            
        } 
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $gift = Gift::where(['id' => $id, 'redeemed' => 0])->first();
        if($gift)
        {
         $user = User::with('roles')->where('email', auth()->user()->email)->first();   
         $role = $user['roles'];
         $role = $role[0]['name'];
         $column = $role."_id";
         if($column == 'agent_id'){
             $request->validate([
                 'userId' => 'required|exists:users,id'
                 ]);
            return Gift::where(['id' => $id, 'redeemed' => 0, 'agent_id' => auth()->user()->id])->update([
             'user_id' => $request['userId']
             ]);
         }
            if($column == 'distributor_id'){
                $request->validate([
                 'agentId' => 'required_without:userId',
                 'userId' => 'required_without:agentId'
                 ]);
            return Gift::where(['id' => $id, 'redeemed' => 0, 'distributor_id' => auth()->user()->id])->update([
             'agent_id' => $request['agentId'],
             'user_id' => $request['userId']
             ]);
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        return Gift::where('id', $id)->delete();
    }

    public function redeem(Request $request)
    {
        $request->validate([
            'code' => 'required|exists:gifts,code|size:6'
        ]);

        $code = $request['code'];

        $data = DB::table('gifts')->where('code', $code)->update([
            'user_id' => auth()->user()->id,
            'redeemed' => true
        ]);
        
    }
    
    public function bulkGifts(Request $request)
    {
        $request->validate([
            'count' => 'required|integer|min:1|max:50',
            'userId' => 'required_without_all:agentId,distributorId',
            'agentId' => 'required_without_all:distributorId,userId',
            'distributorId' => 'required_without_all:agentId,userId',
            'amount' => 'required|numeric',
            'purpose' => 'required',
            'expiry' => 'required'
        ]);
        $data = [];
        for ($i = 0; $i < $request['count']; $i++) {
            array_push($data, ['user_id' => $request['userId'], 'plan_id' => $request['plan'], 'agent_id' => $request['agentId'], 'distributor_id' => $request['distributorId'], 'code' => rand(100001, 999999), 'amount' => $request['amount'], 'purpose' => $request['purpose'], 'redeemed' => 0, 'expiry_at' => $request['expiry'], 'created_at' => now(), 'updated_at' => now()]);
        }
        // $insert = $data;
        return Gift::insert($data);
    }
    
    public function assignedGiftCards()
    {
        $data = DB::table('gifts')->where(function($q){
           $q->where('agent_id', auth()->user()->id) 
             ->orWhere('distributor_id', auth()->user()->id);
        })
        ->get();
        
        return $data;
    }
    
    public function cardDetails(Request $request)
    {
        $request->validate([
            'code' => 'required', 'exists:gifts,code'
        ]);
        return Gift::where('code', $request->code)->first();
    }
}
