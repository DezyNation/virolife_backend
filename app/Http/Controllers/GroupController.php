<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GroupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Group $group)
    {
        return $group->with(['members', 'user'])->all();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $group = User::where('id', auth()->user()->id)->update([
            'code' =>  strtoupper(substr(auth()->user()->name, 0, 4)) . auth()->user()->id,
        ]);

        return $group;
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // $group = Group::with(['members', 'user'])->where(['id' => $id])->get();
        $group = User::where('parent_id', $id)->get();

        return $group;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $group = Group::find($id);

        Group::where(['id' => $id, 'user_id' => auth()->user()->id])->update([
            'title' => $request['title'] ?? $group->title,
            'description' => $request['description'] ?? $group->description,
            'image' => $request['image'] ?? $group->image
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function joinGroup(Request $request, string $id)
    {
        $count = DB::table('users')->where('parent_id', $id)->count();
        if ($count >= 4) {
            return response("Senior has already enough members.", 400);
        }
        $group = User::find($id);
        $user = User::find(auth()->user()->id);
        if (!is_null($request['transactionId']) || !empty($request['transactionId'])) {
            $this->gatewayPayment($request['transactionId'], 250.00, 'primary-group');
        }
        $user->update([
            'primary_transaction_id' => $request['transactionId'],
            'parent_id' => $id,
            'on_hold' => 0
        ]);
        return $group;
    }
    
    public function joinGroupTest(string $user_id, string $group_id, string $payment_id = null)
    {
        // $count = DB::table('users')->where('parent_id', $id)->count();
        // if ($count >= 4) {
        //     return response("Senior has already enough members.", 400);
        // }
        $group = User::find($group_id);
        $user = User::find($user_id);
        // if (!is_null($request['transactionId']) || !empty($request['transactionId'])) {
        //     $this->gatewayPayment($request['transactionId'], 250.00, 'primary-group');
        // }
        $user->update([
            'primary_transaction_id' => $payment_id,
            'parent_id' => $group_id,

        ]);
        return $group;
    }

    public function findGroup(Request $request, string $code)
    {
        $group = User::where('code', $code)->first();
        return $this->joinGroup($request, $group->id);
    }

    public function myGroup()
    {
        $groups = Group::with(['members', 'user.group'])->where(['parent_id' => auth()->user()->id])->get();
        return $groups;
    }

    public function childrenUser()
    {
        $users = DB::select("
        WITH RECURSIVE user_tree AS (
        SELECT u.id, u.name, u.parent_id, u.phone_number, u.email, 1 as level
        FROM users u
        WHERE u.parent_id = :userId
        UNION
        SELECT u2.id, u2.name, u2.parent_id, u2.phone_number, u2.email, user_tree.level+1
        FROM user_tree
        INNER JOIN users as u2 ON user_tree.id = u2.parent_id
        )
        SELECT *
        FROM user_tree ut
        ", ['userId' => auth()->user()->id]);

        return $users;
    }

    public function parents()
    {
        $userId = auth()->user()->id;
        $users = DB::select("
        WITH RECURSIVE user_tree AS (
        SELECT u.id, u0.parent_id as user_parent, u0.name as parent_name, u0.email as parent_email, u0.phone_number as parent_phone, u0.upi_id, u0.id_type, u0.primary_activated, u0.secondary_activated, 1 as level
        FROM users u
        INNER JOIN users as u0 ON u.parent_id = u0.id
        WHERE u.id = :userId
        UNION
        SELECT u2.id, u2.parent_id, u2.name, u2.phone_number, u2.email, u2.upi_id, u2.id_type, u2.primary_activated, u2.secondary_activated, user_tree.level+1
        FROM user_tree
        INNER JOIN users as u2 ON user_tree.user_parent = u2.id
        )
        SELECT *
        FROM user_tree ut
        ", ['userId' => $userId]);

        return $users;
    }

    public function allChildren()
    {
        $primary_users = DB::select("
        WITH RECURSIVE user_tree AS (
        SELECT u.id, u.name, u.parent_id, u.phone_number, u.email, 1 as level
        FROM users u
        WHERE u.parent_id = :userId
        UNION
        SELECT u2.id, u2.name, u2.parent_id, u2.phone_number, u2.email, user_tree.level+1
        FROM user_tree
        INNER JOIN users as u2 ON user_tree.id = u2.parent_id
        )
        SELECT *
        FROM user_tree ut
        ", ['userId' => auth()->user()->id]);

        $secondary_users = DB::select("
        WITH RECURSIVE user_tree AS (
        SELECT u.id, u.name, u.secondary_parent_id, u.phone_number, u.email, 1 as level
        FROM users u
        WHERE u.secondary_parent_id = :userId
        UNION
        SELECT u2.id, u2.name, u2.secondary_parent_id, u2.phone_number, u2.email, user_tree.level
        FROM user_tree
        INNER JOIN users as u2 ON user_tree.id = u2.secondary_parent_id
        )
        SELECT *
        FROM user_tree ut
        ", ['userId' => auth()->user()->id]);

        return ['primary' => $primary_users, 'secondary' => $secondary_users];

        // return $users;
    }


    public function redeem(Request $request, $id)
    {
        $request->validate([
            'code' => 'required|exists:gifts,code|size:6'
        ]);

        $count = DB::table('users')->where('parent_id', $id)->count();
        if ($count >= 4) {
            return response("Senior has already enough members.", 400);
        }

        $code = $request['code'];

        $data = DB::table('gifts')->where(['code' => $code, 'purpose' => 'primary-group'])->update([
            'user_id' => auth()->user()->id,
            'redeemed' => true
        ]);

        if ($data == 0) {
            return response("Bad request", 400);
        }

        return $this->joinGroup($request, $id);
    }
}
