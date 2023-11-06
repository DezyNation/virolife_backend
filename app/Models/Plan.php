<?php

namespace App\Models;

use App\Models\Point;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Plan extends Model
{
    use HasFactory;

    /**
     * Get all of the subscriptions for the Plan
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Get all of the points for the Plan
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function points(): HasMany
    {
        return $this->hasMany(Point::class);
    }
}
