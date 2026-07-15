<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $customers = [
            ['name' => 'Ayesha Khan', 'email' => 'ayesha.khan@example.com', 'phone' => '+1-555-0101', 'address' => '12 Maple Street, Austin, TX', 'is_active' => true],
            ['name' => 'John Miller', 'email' => 'john.miller@example.com', 'phone' => '+1-555-0102', 'address' => '88 Oak Avenue, Dallas, TX', 'is_active' => true],
            ['name' => 'Sara Ali', 'email' => 'sara.ali@example.com', 'phone' => '+1-555-0103', 'address' => '45 Lake View, Houston, TX', 'is_active' => true],
            ['name' => 'David Chen', 'email' => 'david.chen@example.com', 'phone' => '+1-555-0104', 'address' => '9 Riverside Blvd, Austin, TX', 'is_active' => false],
            ['name' => 'Fatima Noor', 'email' => 'fatima.noor@example.com', 'phone' => '+1-555-0105', 'address' => '210 Cedar Lane, San Antonio, TX', 'is_active' => true],
            ['name' => 'Michael Brown', 'email' => 'michael.brown@example.com', 'phone' => '+1-555-0106', 'address' => '77 Pine Road, Dallas, TX', 'is_active' => true],
            ['name' => 'Emma Wilson', 'email' => 'emma.wilson@example.com', 'phone' => '+1-555-0107', 'address' => '310 Sunset Blvd, Houston, TX', 'is_active' => true],
            ['name' => 'Omar Farooq', 'email' => 'omar.farooq@example.com', 'phone' => '+1-555-0108', 'address' => '18 Hillcrest Dr, Austin, TX', 'is_active' => false],
            ['name' => 'Olivia Garcia', 'email' => 'olivia.garcia@example.com', 'phone' => '+1-555-0109', 'address' => '54 Harbor Way, Galveston, TX', 'is_active' => true],
            ['name' => 'Noah Patel', 'email' => 'noah.patel@example.com', 'phone' => '+1-555-0110', 'address' => '66 Market Street, Houston, TX', 'is_active' => true],
            ['name' => 'Hira Ahmed', 'email' => 'hira.ahmed@example.com', 'phone' => '+1-555-0111', 'address' => '91 Crescent Ct, Dallas, TX', 'is_active' => true],
            ['name' => 'Liam Johnson', 'email' => 'liam.johnson@example.com', 'phone' => '+1-555-0112', 'address' => '4 Bay Area Rd, Corpus Christi, TX', 'is_active' => true],
            ['name' => 'Zara Malik', 'email' => 'zara.malik@example.com', 'phone' => '+1-555-0113', 'address' => '128 Green Park, Austin, TX', 'is_active' => true],
            ['name' => 'Ethan Davis', 'email' => 'ethan.davis@example.com', 'phone' => '+1-555-0114', 'address' => '33 North End, Fort Worth, TX', 'is_active' => true],
            ['name' => 'Maya Singh', 'email' => 'maya.singh@example.com', 'phone' => '+1-555-0115', 'address' => '15 Rose Garden, Plano, TX', 'is_active' => false],
            ['name' => 'Jacob Martinez', 'email' => 'jacob.martinez@example.com', 'phone' => '+1-555-0116', 'address' => '200 Elm Street, Austin, TX', 'is_active' => true],
        ];

        foreach ($customers as $index => $customer) {
            $parts = explode(' ', $customer['name'], 2);

            User::updateOrCreate(
                ['email' => $customer['email']],
                [
                    'name' => $customer['name'],
                    'first_name' => $parts[0],
                    'last_name' => $parts[1] ?? '',
                    'password' => Hash::make('password123'),
                    'role' => 'user',
                    'phone' => $customer['phone'],
                    'address' => $customer['address'],
                    'location' => $customer['address'],
                    'is_verified' => 1,
                    'is_active' => $customer['is_active'],
                    'is_banned' => $index === 7,
                    'profile_completed' => 1,
                    'status' => 1,
                    'document_status' => 'approved',
                    'is_notification' => true,
                    'created_at' => now()->subDays(rand(1, 60)),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
