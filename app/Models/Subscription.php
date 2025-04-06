<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plan_id',
        'paystack_subscription_code',
        'paystack_customer_code',
        'authorization_code',
        'status',
        'start_date',
        'end_date',
        'next_payment_date',
        'billing_cycle',
    ];

    protected $dates = [
        'start_date',
        'end_date',
        'next_payment_date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
}
