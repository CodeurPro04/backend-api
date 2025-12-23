<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PropertyType;

class PropertyTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Appartement', 'slug' => 'appartement', 'icon' => 'building'],
            ['name' => 'Villa', 'slug' => 'villa', 'icon' => 'home'],
            ['name' => 'Studio', 'slug' => 'studio', 'icon' => 'door-open'],
            ['name' => 'Terrain', 'slug' => 'terrain', 'icon' => 'map'],
            ['name' => 'Bureau', 'slug' => 'bureau', 'icon' => 'briefcase'],
            ['name' => 'Commerce', 'slug' => 'commerce', 'icon' => 'store'],
            ['name' => 'Entrepôt', 'slug' => 'entrepot', 'icon' => 'warehouse'],
            ['name' => 'Duplex', 'slug' => 'duplex', 'icon' => 'building-columns'],
        ];

        foreach ($types as $type) {
            PropertyType::create($type);
        }
    }
}