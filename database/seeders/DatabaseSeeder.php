<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // No default accounts: the first Admin is created with `php artisan admin:create`.
        $this->call([RoleSeeder::class, InvestorSeeder::class]);
    }
}
