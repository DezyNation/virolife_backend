<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CampaignController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['index', 'show']]);
        $this->middleware('role:admin', ['except' => ['index', 'show', 'store', 'userCampaign']]);
        $this->middleware('block', ['except' => ['index', 'show']]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = Campaign::with(['category', 'user'])->get();
        return $data;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'target_amount' => 'required|numeric',
            'title' => 'required|string',
            'description' => 'required|string',
            'full_description' => 'required|string',
        ]);

        // return filetype($request['files']);
        if ($request->hasFile('files')) {
            // return "files";
            $files = [];
            foreach ($request->file('files') as $file) {
                $file = $file->store('attachment1');
                array_push($files, $file);
            }
            // // $files = $request->file('files');
            // // foreach ($files as $file) {
            // $path = $request->file('files')->store('campaign_assets');
            // // $filepath[] = $path;
            // // }
        }
        // return "no file";
        $data = DB::table('campaigns')->insertGetId([
            'user_id' => auth()->user()->id,
            'category_id' => $request['category_id'],
            'target_amount' => $request['target_amount'],
            'title' => $request['title'],
            'description' => $request['description'],
            'full_description' => $request['full_description'],
            'file_path' => json_encode($files) ?? null,
            'from' => date('Y-m-d', $request['from']),
            'to' => date('Y-m-d', $request['to']),
            'period' => $request['period'],
            'periodicity' => $request['periodicity'],
            'beneficiary_details' => $request['beneficiaryDetails'],
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $data = Campaign::with(['category', 'user'])->where('id', $id)->get();
        return $data;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $campaign = Campaign::find($id);
        if ($request->hasFile('files')) {
            // return "files";
            $files = [];
            foreach ($request->file('files') as $file) {
                $file = $file->store('campaign_assets');
                array_push($files, $file);
                // // $files = $request->file('files');
                // // foreach ($files as $file) {
                // $path = $request->file('files')->store('campaign_assets');
                // // $filepath[] = $path;
                // // }
            }

            $file_path = json_encode($files);
        } else {
            $file_path = $campaign->file_path;
        }
        $data = DB::table('campaigns')->where('id', $id)->update([
            'category_id' => $request['category_id'] ?? $campaign->category_id,
            'title' => $request['title'] ?? $campaign->title,
            'target_amount' => $request['target_amount'] ?? $campaign->target_amount,
            'description' => $request['description'] ?? $campaign->description,
            'from' => date('Y-m-d', $request['from']) ?? $campaign->from,
            'to' => date('Y-m-d', $request['to']) ?? $campaign->to,
            'file_path' => $file_path,
            'period' => $request['period'] ?? $campaign->period,
            'periodicity' => $request['periodicity'] ?? $campaign->periodicity,
            'full_description' => $request['full_description'] ?? $campaign->full_description,
            'status' => $request['status'] ?? $campaign->status,
            'beneficiary_details' => $request['beneficiaryDetails'] ?? $campaign->beneficiary_details,
            'updated_at' => now()
        ]);
        return $data;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $data = DB::table('campaigns')->where('id', $id)->delete();
        return $data;
    }

    public function userCampaign()
    {
        $data = DB::table('campaigns')->where('user_id', auth()->user()->id)->get();
        return $data;
    }

    public function updateAttachment(Request $request, $id)
    {
        $request->validate([
            'filePath' => 'required',
        ]);
        $data = DB::table('campaigns')->where(['user_id' => auth()->user()->id, 'id' => $id])->update([
            'file_path' => $request['filePath']
        ]);

        return $data;
    }
}
