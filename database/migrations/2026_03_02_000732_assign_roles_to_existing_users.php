<?php

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Artisan::call('db:seed', [
            '--class' => RoleAndPermissionSeeder::class,
            '--force' => true,
        ]);

        User::whereDoesntHave('customer')
            ->each(function (User $user): void {
                $user->assignRole('super-admin');
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        User::role('super-admin')
            ->each(function (User $user): void {
                $user->removeRole('super-admin');
            });
    }
};
