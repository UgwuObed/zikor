<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'paystack_plan_id',
        'monthly_price',
        'yearly_price',
        'description',
        'features',
        'is_free',
    ];

    protected $casts = [
        'features' => 'array',
        'is_free' => 'boolean',
    ];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
}
