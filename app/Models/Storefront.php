<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Storefront extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_name',
        'slug',
        'category',
        'logo',
        'banner',
        'tagline',
        'description',
        'email',
        'phone',
        'social_links',
        'color_theme',
        'business_hours',
        'address',
        'bank_details',
        'is_active',
    ];

    protected $casts = [
        'social_links' => 'array',
        'business_hours' => 'array',
        'bank_details' => 'array',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function generateUniqueSlug(string $businessName): string
    {
        $slug = \Str::slug($businessName);
        $originalSlug = $slug;
        $count = 1;

        while (self::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $count++;
        }

        return $slug;
    }
}