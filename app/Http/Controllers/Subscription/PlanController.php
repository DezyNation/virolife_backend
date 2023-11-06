<?php

namespace App\Http\Controllers\Subscription;

use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Subscription\PointController;
use App\Models\Subscription;

class PlanController extends PointController
{

    public function __construct()
    {
        $this->middleware('role:admin', ['except' => ['index', 'show', 'seniorUserPlan']]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Plan::all();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'periodicity' => 'required|numeric',
            'periodicityType' => 'required|string',
            'price' => 'required|min:1|numeric'
        ]);

        $plan = Plan::create([
            "name" => $request['name'],
            "periodicity" => $request['periodicity'],
            "periodicity" => $request['periodicityType'],
            "price" => $request['price']
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return Plan::find($id);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $plan = Plan::find($id);

        Plan::where('id', $id)->update([
            "name" => $request['name'] ?? $plan->name,
            "periodicity" => $request['periodicity'] ?? $plan->periodicity,
            "periodicity_type" => $request['periodicityType'] ?? $plan->periodicity_type,
            "price" => $request['price'] ?? $plan->price
        ]);

        return $plan;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function seniorUserPlan()
    {
        return Subscription::with('plan')->where('user_id', auth()->user()->parent_id)->select('id', 'plan_id', 'user_id')->get();
    }
}
