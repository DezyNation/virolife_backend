<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BankController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $bank = Bank::with('user')->get();
        return $bank;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'accountNumber' => 'required|unique:banks,account_number',
            'ifsc' => 'required',
            'bankName' => 'required'
        ]);
        
        $data = Bank::create([
            'user_id' => auth()->user()->id,
            'account_number' => $request['accountNumber'],
            'ifsc' => $request['ifsc'],
            'bank_name' => $request['bankName'],
            'verified' => 0
        ]);

        return $data;
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $data = Bank::with('user')->find($id);
        return $data;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'accountNumber' => 'required|unique:banks,account_number',
            'ifsc' => 'required',
            'bankName' => 'required'
        ]);

        $data = Bank::where('id', $id)->update([
            'user_id' => auth()->user()->id,
            'account_number' => $request['accountNumber'],
            'ifsc' => $request['ifsc'],
            'bank_name' => $request['bankName'],
            'verified' => 0
        ]);

        return $data;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $data = Bank::where('id', $id)->delete();
        return $data;
    }

    /**
     * Get user specific resources.
     */
    public function userBanks()
    {
        $data = Bank::with('user')->where('user_id', auth()->user()->id)->get();
        return $data;
    }
}
