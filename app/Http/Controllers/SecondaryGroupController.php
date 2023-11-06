<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SecondaryGroupController extends Controller
{
    public function joinGroup(Request $request): JsonResponse
    {
        $junior_id = auth()->user()->id;
        if (!is_null(auth()->user()->secondary_parent_id)) {
            return response()->json(["message" => "You can not join more than two groups"], 400);
        }
        $senior = DB::table('users')
            ->where('id', '!=', $junior_id)
            ->where('secondary_activation_enable', true)
            ->where('secondary_parent_id', '!=' ,null)
            ->select('id', 'name', 'parent_id', DB::raw('COUNT(parent_id) as count'))
            ->groupBy('id', 'name', 'parent_id')
            ->having('count', '<', 4)
            ->inRandomOrder()
            ->first();

        $senior_id = $senior->id;

        $parent = User::find($senior_id);
        if (!is_null($request['transactionId']) || !empty($request['transactionId'])) {
            $this->gatewayPayment($request['transactionId'], 500.00, 'secondary-group');
        }

        DB::table('users')->where('id', $junior_id)->update(['secondary_parent_id' => $senior_id, 'updated_at' => now(), 'secondary_transaction_id' => $request['transactionId']]);

        return response()->json(['message' => 'Group joined.', 'parent' => $parent]);
    }
    
    public function joinGroupTest(string $junior_id, string $transaction_id = null): JsonResponse
    {
        $senior = DB::table('users')
            ->where('id', '!=', $junior_id)
            ->where('secondary_activation_enable', true)
            ->where('secondary_parent_id', '!=', null)
            ->select('id', 'name', 'parent_id', DB::raw('COUNT(parent_id) as count'))
            ->groupBy('id', 'name', 'parent_id')
            ->having('count', '<', 4)
            ->inRandomOrder()
            ->first();

        $senior_id = $senior->id;

        $parent = User::find($senior_id);

        DB::table('users')->where('id', $junior_id)->update(['secondary_parent_id' => $senior_id, 'updated_at' => now(), 'secondary_transaction_id' => $transaction_id]);

        return response()->json(['message' => 'Group joined.', 'parent' => $parent]);
    }

    public function secondryChildren()
    {
        $users = DB::select("
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

        return $users;
    }

    public function secondryParents()
    {
        $userId = auth()->user()->id;
        $users = DB::select("
        WITH RECURSIVE user_tree AS (
        SELECT u.id, u0.secondary_parent_id as user_parent, u0.name as parent_name, u0.email as parent_email, u0.phone_number as parent_phone, u0.upi_id, u0.id_type, u0.primary_activated, u0.secondary_activated, 1 as level
        FROM users u
        INNER JOIN users as u0 ON u.secondary_parent_id = u0.id
        WHERE u.id = :userId
        UNION
        SELECT u2.id, u2.secondary_parent_id, u2.name, u2.phone_number, u2.email, u2.upi_id, u2.id_type, u2.primary_activated, u2.secondary_activated, user_tree.level+1
        FROM user_tree
        INNER JOIN users as u2 ON user_tree.user_parent = u2.id
        )
        SELECT *
        FROM user_tree ut
        ", ['userId' => $userId]);

        return $users;
    }

    public function redeem(Request $request)
    {
        $request->validate([
            'code' => 'required|exists:gifts,code|size:6'
        ]);

        $code = $request['code'];

        $data = DB::table('gifts')->where(['code' => $code, 'purpose' => 'secondary-group'])->update([
            'user_id' => auth()->user()->id,
            'redeemed' => true
        ]);

        if ($data == 0) {
            return response("Bad request", 400);
        }

        return $this->joinGroup($request);
    }
}
