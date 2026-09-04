<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            CountrySeeder::class,
            AdminSeeder::class,
            PropertyTypeSeeder::class,
            PropertyFeatureSeeder::class,
            DemoDataSeeder::class,
            ProductAddressSeeder::class,
        ]);
    }
}
