<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@curbcream.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'is_verified' => 1,
                'is_active' => 1,
                'profile_completed' => 1,
                'status' => 1,
                'document_status' => 'approved',
                'is_banned' => false,
            ]
        );

        foreach ([
            [
                'name' => 'Starter Monthly',
                'billing_cycle' => 'monthly',
                'price' => 29.99,
                'discount_percent' => 0,
                'is_promotional' => false,
                'description' => 'Basic monthly plan for service providers',
            ],
            [
                'name' => 'Growth Quarterly',
                'billing_cycle' => 'quarterly',
                'price' => 79.99,
                'discount_percent' => 10,
                'is_promotional' => true,
                'description' => 'Quarterly plan with promotional discount',
            ],
            [
                'name' => 'Pro Yearly',
                'billing_cycle' => 'yearly',
                'price' => 249.99,
                'discount_percent' => 20,
                'is_promotional' => true,
                'description' => 'Best value annual subscription',
            ],
        ] as $plan) {
            SubscriptionPlan::updateOrCreate(
                ['name' => $plan['name']],
                array_merge($plan, ['is_active' => true])
            );
        }
    }
}
