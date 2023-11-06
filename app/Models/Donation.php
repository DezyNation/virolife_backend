<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Donation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'approved',
        'donatable_type',
        'donatable_id',
        'updated_by',
        'user_id',
        'group',
        'donated_to',
        'amount',
        'remarks'
    ];

    public function donatable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user that owns the Donation
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function updatedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
