<?php

namespace App\Models;

use App\Models\Campaign;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'type',
        'name',
        'status'
        ];

    /**
     * Get all of the campaigns for the Category
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }
}
