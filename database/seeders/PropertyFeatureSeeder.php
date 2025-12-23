<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PropertyFeature;

class PropertyFeatureSeeder extends Seeder
{
    public function run(): void
    {
        $features = [
            // Confort
            ['name' => 'Climatisation', 'icon' => 'fan', 'category' => 'confort'],
            ['name' => 'Chauffage', 'icon' => 'fire', 'category' => 'confort'],
            ['name' => 'Piscine', 'icon' => 'water', 'category' => 'confort'],
            ['name' => 'Jardin', 'icon' => 'tree', 'category' => 'confort'],
            ['name' => 'Balcon', 'icon' => 'door-open', 'category' => 'confort'],
            ['name' => 'Terrasse', 'icon' => 'umbrella', 'category' => 'confort'],
            
            // Sécurité
            ['name' => 'Gardiennage', 'icon' => 'shield', 'category' => 'securite'],
            ['name' => 'Vidéosurveillance', 'icon' => 'camera', 'category' => 'securite'],
            ['name' => 'Alarme', 'icon' => 'bell', 'category' => 'securite'],
            ['name' => 'Portail électrique', 'icon' => 'gate', 'category' => 'securite'],
            ['name' => 'Interphone', 'icon' => 'phone', 'category' => 'securite'],
            
            // Équipements
            ['name' => 'Cuisine équipée', 'icon' => 'utensils', 'category' => 'equipements'],
            ['name' => 'Meublé', 'icon' => 'couch', 'category' => 'equipements'],
            ['name' => 'Internet/Wifi', 'icon' => 'wifi', 'category' => 'equipements'],
            ['name' => 'Ascenseur', 'icon' => 'elevator', 'category' => 'equipements'],
            ['name' => 'Garage', 'icon' => 'car', 'category' => 'equipements'],
            ['name' => 'Parking', 'icon' => 'parking', 'category' => 'equipements'],
            ['name' => 'Générateur', 'icon' => 'plug', 'category' => 'equipements'],
            ['name' => 'Eau courante', 'icon' => 'droplet', 'category' => 'equipements'],
        ];

        foreach ($features as $feature) {
            PropertyFeature::create($feature);
        }
    }
}