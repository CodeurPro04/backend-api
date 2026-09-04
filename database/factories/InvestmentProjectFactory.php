<?php

namespace Database\Factories;

use App\Models\Country;
use App\Models\InvestmentProject;
use App\Models\User;
use App\Support\CountryLocations;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<InvestmentProject>
 */
class InvestmentProjectFactory extends Factory
{
    protected $model = InvestmentProject::class;

    public function definition(): array
    {
        $total = fake()->numberBetween(30_000_000, 2_500_000_000);
        $min = fake()->numberBetween(250_000, 25_000_000);

        $creatorId = User::query()->whereHas('role', fn ($q) => $q->whereIn('slug', ['agent', 'entreprise']))->inRandomOrder()->value('id')
            ?: User::query()->whereHas('role', fn ($q) => $q->where('slug', 'admin'))->inRandomOrder()->value('id');
        $countryId = User::query()->where('id', $creatorId)->value('country_id')
            ?: Country::query()->inRandomOrder()->value('id');
        $place = CountryLocations::forCountryId($countryId);
        $title = fake()->randomElement([
            "Résidence premium",
            "Immeuble locatif",
            "Lotissement sécurisé",
            "Rénovation d'appartements",
            "Construction de villas",
        ]) . " - " . $place['city'];

        return [
            'country_id' => $countryId,
            'created_by' => $creatorId,
            'title' => $title,
            'slug' => Str::slug($title) . '-' . Str::random(6),
            'description' => fake()->optional(0.9)->paragraphs(fake()->numberBetween(2, 5), true),
            'project_type' => fake()->randomElement(['immobilier', 'construction', 'renovation']),
            'location' => $place['address'],
            'city' => $place['city'],
            'reference_code' => fake()->optional(0.7)->bothify('ABI-INV-####'),
            'postal_code' => $place['postal_code'],
            'surface_area' => fake()->optional(0.7)->randomFloat(2, 100, 5000),
            'total_investment' => $total,
            'min_investment' => min($min, $total),
            'expected_return' => fake()->randomFloat(2, 6, 24),
            'duration_months' => fake()->numberBetween(6, 48),
            'status' => fake()->randomElement(['open', 'in_progress', 'closed', 'completed']),
            'approval_status' => fake()->randomElement(['pending', 'approved', 'rejected']),
            'rejection_reason' => null,
            'start_date' => fake()->optional(0.6)->dateTimeBetween('-3 months', '+3 months'),
            'end_date' => null,
            'documents_path' => [],
            'images_path' => [],
            'plans_path' => [],
            'render_3d_path' => [],
            'current_funding' => fake()->numberBetween(0, (int) ($total * 0.9)),
            'investors_count' => fake()->numberBetween(0, 50),
            'featured' => fake()->boolean(15),
        ];
    }
}
