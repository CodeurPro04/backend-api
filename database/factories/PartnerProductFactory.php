<?php

namespace Database\Factories;

use App\Models\PartnerProduct;
use App\Models\Partnership;
use App\Models\User;
use App\Support\CountryLocations;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PartnerProduct>
 */
class PartnerProductFactory extends Factory
{
    protected $model = PartnerProduct::class;

    public function definition(): array
    {
        $partnership = Partnership::query()->inRandomOrder()->first();
        $companyType = $partnership?->company_type ?? 'immobilier';
        $place = CountryLocations::forCountryId($partnership?->country_id);

        $typeData = match ($companyType) {
            'investisseur' => [
                'financing_type' => fake()->randomElement(['crédit', 'leasing', 'investissement']),
                'interest_rate_min' => fake()->randomFloat(2, 4, 12),
                'interest_rate_max' => fake()->randomFloat(2, 12, 25),
                'amount_min' => fake()->numberBetween(500_000, 10_000_000),
                'amount_max' => fake()->numberBetween(25_000_000, 500_000_000),
                'duration_months' => fake()->numberBetween(6, 60),
                'conditions' => fake()->sentence(),
                'target_audience' => fake()->randomElement(['Particuliers', 'PME', 'Investisseurs']),
            ],
            'constructeur' => [
                'construction_type' => fake()->randomElement(['villa', 'immeuble', 'rénovation']),
                'location' => $place['location'],
                'address' => $place['address'],
                'city' => $place['city'],
                'surface_min' => fake()->numberBetween(60, 150),
                'surface_max' => fake()->numberBetween(200, 1200),
                'price_per_sqm' => fake()->numberBetween(120_000, 450_000),
                'delivery_date' => fake()->dateTimeBetween('+2 months', '+18 months')->format('Y-m-d'),
                'materials' => fake()->randomElements(['Béton', 'Brique', 'Métal', 'Bois'], fake()->numberBetween(1, 3)),
            ],
            default => [
                'property_type' => fake()->randomElement(['appartement', 'villa', 'terrain', 'bureau']),
                'transaction_type' => fake()->randomElement(['vente', 'location']),
                'location' => $place['location'],
                'address' => $place['address'],
                'city' => $place['city'],
                'surface' => fake()->numberBetween(25, 450),
                'rooms' => fake()->numberBetween(1, 7),
                'bathrooms' => fake()->numberBetween(1, 4),
            ],
        };

        $status = fake()->randomElement(['pending', 'approved', 'rejected']);
        $approverId = User::query()->whereHas('role', fn ($q) => $q->whereIn('slug', ['admin', 'gestionnaire']))->inRandomOrder()->value('id');

        return [
            'country_id' => $partnership?->country_id,
            'partnership_id' => $partnership?->id,
            'title' => fake()->randomElement(['Offre premium', 'Programme résidentiel', 'Solution habitat', 'Projet clé en main']) . ' - ' . $place['city'],
            'description' => fake()->optional(0.9)->paragraphs(2, true),
            'price' => fake()->optional(0.8)->numberBetween(250_000, 900_000_000),
            'currency' => 'XOF',
            'images' => [],
            'type_data' => $typeData,
            'status' => $status,
            'rejection_reason' => $status === 'rejected' ? fake()->sentence() : null,
            'approved_by' => $status === 'approved' ? $approverId : null,
            'approved_at' => $status === 'approved' ? now() : null,
        ];
    }
}
