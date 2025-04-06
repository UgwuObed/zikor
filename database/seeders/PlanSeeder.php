<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Plan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        Plan::create([
            'name' => 'Starter',
            'monthly_price' => 0,
            'yearly_price' => 0,
            'description' => 'For new businesses just getting started',
            'is_free' => true,
            'features' => json_encode([
                'Basic storefront',
                'Up to 100 products',
                'Basic payment integration'
            ])
        ]);
        
        Plan::create([
            'name' => 'Pro',
            'monthly_price' => 4500,
            'yearly_price' => 45000,
            'description' => 'For growing businesses ready to scale',
            'is_free' => false,
            'features' => json_encode([
                'Full-featured storefront',
                'Unlimited Products',
                'Instagram product import',
                'AI Business Assistant'
            ])
        ]);
        
        Plan::create([
            'name' => 'Business',
            'monthly_price' => 15000,
            'yearly_price' => 150000,
            'description' => 'For established businesses seeking growth',
            'is_free' => false,
            'features' => json_encode([
                'Everything in Pro, plus:',
                'Custom domain included',
                'Real-time logistics tracking',
                'Dedicated account representative'
            ])
        ]);
    }
}

