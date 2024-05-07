<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ZikorPhoneNumber extends Model
{
    protected $fillable = ['user_id', 'phone_number', 'friendly_name'];
}
