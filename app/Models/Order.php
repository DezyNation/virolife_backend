<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_id',
        'amount',
        'status',
        'razorpay_payment_id',
        'razorpay_payment_signature',
        'receipt',
        'razorpay_timesamp',
        'purpose',
        'metadata',
        'health_points',
        'intent',
        'atp_stars',
        'ad_points',
        'product_id'
    ];
}
