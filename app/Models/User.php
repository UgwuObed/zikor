<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Support\Facades\Hash;



class User extends Authenticatable 
{
    use  HasApiTokens, HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'business_name',
        'email',
        'password',
        'phone',
        'state',
        'country',
        'city',
        'is_admin',
        'unique_identifier',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime:Y-m-d\TH:i:s\Z',
    ];


    // public function storefront(): HasOne
    // {
    //     return $this->hasOne(Storefront::class);
    // }
    
    public function storefront()
        {
            return $this->hasOne(Storefront::class);
        }


    public function products()
    {
        return $this->hasMany(Product::class);
    }


    /**
     * Get the JWT identifier.
     *
     * @return int
     */
    public function getJWTIdentifier()
    {
        return $this->id;
    }

    /**
     * Get the JWT token claim.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    // Accessor method to check if the user is an admin
    public function isAdmin()
    {
        return $this->is_admin;
    }
}