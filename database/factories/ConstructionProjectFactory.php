<?php

namespace Database\Factories;

use App\Models\ConstructionProject;
use App\Models\User;
use App\Support\CountryLocations;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConstructionProject>
 */
class ConstructionProjectFactory extends Factory
{
    protected $model = ConstructionProject::class;

    public function definition(): array
    {
        $visitorId = User::query()->whereHas('role', fn ($q) => $q->where('slug', 'visiteur'))->inRandomOrder()->value('id');
        $agentId = User::query()->whereHas('role', fn ($q) => $q->where('slug', 'agent'))->inRandomOrder()->value('id');
        $countryId = User::query()->where('id', $visitorId)->value('country_id')
            ?: User::query()->where('id', $agentId)->value('country_id');
        $place = CountryLocations::forCountryId($countryId);

        $status = fake()->randomElement(['submitted', 'published', 'in_study', 'quoted', 'approved', 'rejected', 'in_progress', 'completed']);

        $budgetMin = fake()->optional(0.9)->numberBetween(5_000_000, 120_000_000);
        $budgetMax = $budgetMin ? $budgetMin + fake()->numberBetween(1_000_000, 250_000_000) : null;

        $isPublication = $status === 'published' ? true : fake()->boolean(20);

        return [
            'country_id' => $countryId,
            'user_id' => $visitorId,
            'agent_id' => fake()->optional(0.6)->passthrough($agentId),
            'title' => fake()->randomElement(['Villa clé en main', 'Résidence familiale', 'Immeuble R+3', 'Maison contemporaine']) . ' - ' . $place['city'],
            'description' => fake()->optional(0.9)->paragraphs(fake()->numberBetween(2, 5), true),
            'project_type' => fake()->randomElement(['residential', 'commercial', 'industrial']),
            'budget_min' => $budgetMin,
            'budget_max' => $budgetMax,
            'surface_area' => fake()->optional(0.7)->randomFloat(2, 50, 800),
            'location' => $place['address'],
            'city' => $place['city'],
            'status' => $status,
            'is_publication' => $isPublication,
            'rejection_reason' => $status === 'rejected' ? fake()->sentence() : null,
            'plan_3d_path' => null,
            'documents_path' => [],
            'images_path' => $status === 'published' ? ['seed/placeholder.png'] : [],
            'plans_path' => $status === 'published' ? ['seed/placeholder.png'] : [],
            'render_3d_path' => [],
            'estimated_duration' => fake()->optional(0.5)->numberBetween(3, 18),
            'start_date' => fake()->optional(0.4)->dateTimeBetween('-6 months', '+2 months'),
            'end_date' => null,
        ];
    }
}
