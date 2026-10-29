<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * The app starts empty for a real user. Run `php artisan db:seed` only to
     * explore it with the sample taxpayer from the design.
     */
    public function run(): void
    {
        $this->call(DemoTaxpayerSeeder::class);
    }
}
