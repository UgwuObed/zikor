<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PendingPayment extends Model
{
    use HasFactory;

   
    protected $table = 'pending_payments';


    protected $fillable = [
        'user_id', 
        'plan_id', 
        'billing_cycle', 
        'reference', 
        'status',
    ];

   
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

   
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
