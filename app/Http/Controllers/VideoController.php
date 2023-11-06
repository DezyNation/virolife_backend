<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Video;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VideoController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:admin', ['except' => ['index', 'show', 'randomVideo']]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = Video::all();

        return $data;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = Video::create([
            'title' => $request['title'],
            'description' => $request['description'],
            'link' => $request['link'],
            'thumbnail' => $request['thumbnail'],
            'metadata' => $request['metadata'],
            'minimum_duration' => $request['duration'],
            'is_active' => $request['isActive'],
            'points' => $request['points'],
            'video_id' => $request['video_id']
        ]);

        return response($data);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $data = Video::findOrFail($id);
        return $data;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $video = Video::findOrFail($id);

        $video->update([
            'title' => $request['title'] ?? $video->title,
            'description' => $request['description'] ?? $video->description,
            'link' => $request['link'] ?? $video->link,
            'thumbnail' => $request['thumbnail'] ?? $video->thumbnail,
            'metadata' => $request['metadata'] ?? $video->metadata,
            'is_active' => $request['is_active'] ?? $video->is_active,
            'points' => $request['points'] ?? $video->points,
            'minimum_duration' => $request['duration'] ?? $video->minimum_duration,
            'video_id' => $request['video_id'] ?? $video->video_id
        ]);

        return $video;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $data = Video::where('id', $id)->delete();

        return $data;
    }

    public function randomVideo(): Model
    {
        $data = Video::where('is_active', 1)->inRandomOrder()->first();
        $this->videoPoints($data->id, $data->points);
        return $data;
    }

    public function videoPoints($video_id, $credit)
    {
        $user_id = auth()->user()->id;
        $user = User::find($user_id);
        DB::table('user_video')->insert([
            'user_id' => $user_id,
            'video_id' => $video_id,
            'credits' => $credit,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        User::where('id', $user_id)->update(['ad_points' => auth()->user()->ad_points + $credit]);
        $transaction_id = uniqid("VIR-VID");
        $opening_balance = $user->points;
        $closing_balance = $opening_balance + $credit;
        $metadata = [
            'event' => 'video.credit'
        ];
        $this->transaction($user_id, null, $transaction_id, 'App\Models\Video', $video_id, $credit, 0, $opening_balance, $closing_balance, json_encode($metadata));
    }
}
