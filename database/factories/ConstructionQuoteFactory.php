<?php

namespace Database\Factories;

use App\Models\ConstructionProject;
use App\Models\ConstructionQuote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConstructionQuote>
 */
class ConstructionQuoteFactory extends Factory
{
    protected $model = ConstructionQuote::class;

    public function definition(): array
    {
        $agentId = User::query()->whereHas('role', fn ($q) => $q->where('slug', 'agent'))->inRandomOrder()->value('id');

        return [
            'construction_project_id' => ConstructionProject::query()->inRandomOrder()->value('id'),
            'agent_id' => $agentId,
            'quote_number' => 'QT-' . fake()->unique()->numerify('########'),
            'total_amount' => fake()->numberBetween(2_000_000, 250_000_000),
            'currency' => 'XOF',
            'validity_days' => fake()->numberBetween(7, 30),
            'status' => fake()->randomElement(['draft', 'sent', 'accepted', 'rejected']),
            'notes' => fake()->optional(0.6)->sentence(),
            'file_path' => null,
            'sent_at' => null,
            'responded_at' => null,
        ];
    }
}

