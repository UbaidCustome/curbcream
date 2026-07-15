<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ProviderSeeder extends Seeder
{
    public function run(): void
    {
        $providers = [
            [
                'name' => 'Cool Scoops Truck',
                'email' => 'cool.scoops@example.com',
                'phone' => '+1-555-1001',
                'location' => 'Downtown Austin',
                'document_status' => 'approved',
                'is_active' => true,
                'subscription_plan' => 'Starter Monthly',
                'subscription_status' => 'active',
                'products' => [
                    ['name' => 'Vanilla Scoop', 'price' => 4.50],
                    ['name' => 'Chocolate Cone', 'price' => 5.00],
                ],
            ],
            [
                'name' => 'Berry Bliss Ice Cream',
                'email' => 'berry.bliss@example.com',
                'phone' => '+1-555-1002',
                'location' => 'Dallas Midtown',
                'document_status' => 'approved',
                'is_active' => true,
                'subscription_plan' => 'Pro Yearly',
                'subscription_status' => 'active',
                'products' => [
                    ['name' => 'Strawberry Cup', 'price' => 5.50],
                    ['name' => 'Mixed Berry Sundae', 'price' => 7.00],
                ],
            ],
            [
                'name' => 'Frosty Wheels',
                'email' => 'frosty.wheels@example.com',
                'phone' => '+1-555-1003',
                'location' => 'Houston Heights',
                'document_status' => 'pending',
                'is_active' => false,
                'subscription_plan' => null,
                'subscription_status' => 'none',
                'products' => [
                    ['name' => 'Mint Chip', 'price' => 4.75],
                ],
            ],
            [
                'name' => 'Sunny Softserve',
                'email' => 'sunny.softserve@example.com',
                'phone' => '+1-555-1004',
                'location' => 'San Antonio Riverwalk',
                'document_status' => 'approved',
                'is_active' => true,
                'subscription_plan' => 'Growth Quarterly',
                'subscription_status' => 'active',
                'products' => [
                    ['name' => 'Soft Serve Swirl', 'price' => 3.99],
                    ['name' => 'Caramel Dip Cone', 'price' => 4.99],
                ],
            ],
            [
                'name' => 'Polar Pop Truck',
                'email' => 'polar.pop@example.com',
                'phone' => '+1-555-1005',
                'location' => 'Plano Downtown',
                'document_status' => 'more_info',
                'is_active' => false,
                'subscription_plan' => 'Starter Monthly',
                'subscription_status' => 'expired',
                'products' => [
                    ['name' => 'Cookie Dough Scoop', 'price' => 5.25],
                ],
            ],
            [
                'name' => 'Creamy Cruise',
                'email' => 'creamy.cruise@example.com',
                'phone' => '+1-555-1006',
                'location' => 'Fort Worth Stockyards',
                'document_status' => 'approved',
                'is_active' => true,
                'subscription_plan' => 'Starter Monthly',
                'subscription_status' => 'active',
                'products' => [
                    ['name' => 'Pistachio Cone', 'price' => 5.75],
                    ['name' => 'Mango Sorbet', 'price' => 4.25],
                ],
            ],
            [
                'name' => 'Icy Express',
                'email' => 'icy.express@example.com',
                'phone' => '+1-555-1007',
                'location' => 'Galveston Beach',
                'document_status' => 'rejected',
                'is_active' => false,
                'subscription_plan' => null,
                'subscription_status' => 'none',
                'products' => [],
            ],
            [
                'name' => 'Rainbow Parlor On Wheels',
                'email' => 'rainbow.parlor@example.com',
                'phone' => '+1-555-1008',
                'location' => 'Austin East Side',
                'document_status' => 'approved',
                'is_active' => true,
                'subscription_plan' => 'Pro Yearly',
                'subscription_status' => 'active',
                'products' => [
                    ['name' => 'Rainbow Sherbet', 'price' => 4.80],
                    ['name' => 'Cotton Candy Scoop', 'price' => 5.10],
                    ['name' => 'Fudge Brownie Cup', 'price' => 6.50],
                ],
            ],
        ];

        foreach ($providers as $index => $provider) {
            $user = User::updateOrCreate(
                ['email' => $provider['email']],
                [
                    'name' => $provider['name'],
                    'business_name' => $provider['name'],
                    'password' => Hash::make('password123'),
                    'role' => 'driver',
                    'phone' => $provider['phone'],
                    'location' => $provider['location'],
                    'address' => $provider['location'],
                    'bio' => 'Mobile ice cream service for parks, events, and neighborhoods.',
                    'vehicle_category' => 'Ice Cream Truck',
                    'open_time' => '10:00:00',
                    'close_time' => '21:00:00',
                    'is_verified' => 1,
                    'is_active' => $provider['is_active'],
                    'is_banned' => $index === 6,
                    'profile_completed' => 1,
                    'status' => 1,
                    'document_status' => $provider['document_status'],
                    'admin_notes' => $provider['document_status'] === 'more_info'
                        ? 'Please upload a clearer business license.'
                        : null,
                    'subscription_plan' => $provider['subscription_plan'],
                    'subscription_status' => $provider['subscription_status'],
                    'subscription_expires_at' => $provider['subscription_status'] === 'active'
                        ? now()->addDays(rand(10, 120))
                        : ($provider['subscription_status'] === 'expired' ? now()->subDays(rand(5, 40)) : null),
                    'is_notification' => true,
                    'created_at' => now()->subDays(rand(5, 90)),
                    'updated_at' => now(),
                ]
            );

            foreach ($provider['products'] as $productIndex => $product) {
                Product::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'name' => $product['name'],
                    ],
                    [
                        'price' => $product['price'],
                        'is_featured' => $productIndex === 0 && $provider['document_status'] === 'approved',
                        'images' => [],
                    ]
                );
            }
        }
    }
}
