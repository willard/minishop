<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleAndPermissionSeeder::class);

        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        $user->assignRole('super-admin');

        $this->call([
            CategorySeeder::class,
            ProductSeeder::class,
            OrderSeeder::class,
            CouponSeeder::class,
            ShippingMethodSeeder::class,
        ]);
    }
}
