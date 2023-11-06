<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
    'user_id',
    'campaign_id',
    'transaction_id',
    'purchasable_type',
    'purchasable_id',
    'credit',
    'debit',
    'opening_balance',
    'closing_balance',
    'metadata'
    ];
}
