<?php

namespace Database\Seeders;

use App\Models\ConstructionProject;
use App\Models\InvestmentProject;
use App\Models\PartnerProduct;
use App\Models\Partnership;
use App\Models\Property;
use App\Support\CountryLocations;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductAddressSeeder extends Seeder
{
    public function run(): void
    {
        Property::query()->whereNotNull('country_id')->orderBy('id')->get()->each(function (Property $property) {
            $place = CountryLocations::forCountryId($property->country_id);
            $baseTitle = $this->propertyTitle($property);

            $property->update([
                'title' => "{$baseTitle} {$place['city']}",
                'slug' => Str::slug("{$baseTitle} {$place['city']}") . '-' . Str::lower(Str::random(6)),
                'address' => $place['address'],
                'city' => $place['city'],
                'commune' => $place['commune'],
                'quartier' => $place['quartier'],
            ]);
        });

        ConstructionProject::query()->whereNotNull('country_id')->orderBy('id')->get()->each(function (ConstructionProject $project) {
            $place = CountryLocations::forCountryId($project->country_id);
            $label = match ($project->project_type) {
                'commercial' => 'Complexe commercial',
                'industrial' => 'Site industriel',
                default => 'Résidence familiale',
            };

            $project->update([
                'title' => "{$label} - {$place['city']}",
                'location' => $place['address'],
                'city' => $place['city'],
            ]);
        });

        InvestmentProject::query()->whereNotNull('country_id')->orderBy('id')->get()->each(function (InvestmentProject $project) {
            $place = CountryLocations::forCountryId($project->country_id);
            $label = match ($project->project_type) {
                'construction' => 'Programme de construction',
                'renovation' => 'Rénovation patrimoniale',
                default => 'Résidence premium',
            };

            $project->update([
                'title' => "{$label} - {$place['city']}",
                'slug' => Str::slug("{$label} {$place['city']}") . '-' . Str::lower(Str::random(6)),
                'location' => $place['address'],
                'city' => $place['city'],
                'postal_code' => $place['postal_code'],
            ]);
        });

        Partnership::query()->whereNotNull('country_id')->orderBy('id')->get()->each(function (Partnership $partnership) {
            $place = CountryLocations::forCountryId($partnership->country_id);

            $partnership->update([
                'address' => $place['address'],
                'city' => $place['city'],
            ]);
        });

        PartnerProduct::query()->whereNotNull('country_id')->with('partnership')->orderBy('id')->get()->each(function (PartnerProduct $product) {
            $place = CountryLocations::forCountryId($product->country_id);
            $typeData = $product->type_data ?? [];

            $typeData['location'] = $place['location'];
            $typeData['address'] = $place['address'];
            $typeData['city'] = $place['city'];
            $typeData['country_code'] = $place['country_code'];
            $typeData['country_name'] = $place['country_name'];

            $product->update([
                'title' => "{$this->productTitle($product)} - {$place['city']}",
                'type_data' => $typeData,
            ]);
        });

        $this->command?->info('Adresses pays des produits et projets mises à jour.');
    }

    private function propertyTitle(Property $property): string
    {
        return match ($property->transaction_type) {
            'location' => 'Bien à louer',
            default => 'Bien immobilier',
        };
    }

    private function productTitle(PartnerProduct $product): string
    {
        return match ($product->partnership?->company_type) {
            'constructeur' => 'Projet constructeur',
            'investisseur' => 'Offre investissement',
            default => 'Produit immobilier',
        };
    }
}
