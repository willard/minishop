<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Minishop\Database\Seeders\MinishopSeeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(MinishopSeeder::class);
    }
}
