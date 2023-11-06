<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'link',
        'thumbnail',
        'metadata',
        'is_active',
        'minimum_duration',
        'points',
        'video_id'
    ];
}
