<?php

namespace Database\Factories;

use App\Models\PropertyType;
use App\Models\SearchRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SearchRequest>
 */
class SearchRequestFactory extends Factory
{
    protected $model = SearchRequest::class;

    public function definition(): array
    {
        $visitorId = User::query()->whereHas('role', fn ($q) => $q->where('slug', 'visiteur'))->inRandomOrder()->value('id');
        $agentId = User::query()->whereHas('role', fn ($q) => $q->where('slug', 'agent'))->inRandomOrder()->value('id');
        $countryId = User::query()->where('id', $visitorId)->value('country_id');

        $status = fake()->randomElement(['pending', 'assigned', 'in_progress', 'fulfilled', 'cancelled']);
        $assignedAt = in_array($status, ['assigned', 'in_progress', 'fulfilled'], true) ? fake()->dateTimeThisYear() : null;
        $fulfilledAt = $status === 'fulfilled' ? fake()->dateTimeThisYear() : null;

        $budgetMin = fake()->optional(0.8)->numberBetween(5_000_000, 120_000_000);
        $budgetMax = $budgetMin ? $budgetMin + fake()->numberBetween(1_000_000, 250_000_000) : null;

        return [
            'country_id' => $countryId,
            'user_id' => $visitorId,
            'agent_id' => $assignedAt ? $agentId : null,
            'property_type_id' => PropertyType::query()->inRandomOrder()->value('id'),
            'transaction_type' => fake()->randomElement(['vente', 'location']),
            'budget_min' => $budgetMin,
            'budget_max' => $budgetMax,
            'location_preferences' => fake()->optional(0.9)->randomElements(
                ['Cocody', 'Yopougon', 'Plateau', 'Marcory', 'Riviera', 'Angré'],
                fake()->numberBetween(1, 3)
            ),
            'bedrooms_min' => fake()->optional(0.7)->numberBetween(1, 5),
            'surface_min' => fake()->optional(0.6)->randomFloat(2, 30, 400),
            'additional_requirements' => fake()->optional(0.7)->sentence(),
            'status' => $status,
            'priority' => fake()->randomElement(['low', 'medium', 'high']),
            'rejection_reason' => null,
            'approved_at' => null,
            'rejected_at' => null,
            'assigned_at' => $assignedAt,
            'fulfilled_at' => $fulfilledAt,
            'deal_status' => $fulfilledAt ? fake()->randomElement(['won', 'lost']) : null,
            'deal_concluded_at' => $fulfilledAt ? fake()->dateTimeThisYear() : null,
            'deal_sale_price' => $fulfilledAt ? (string) fake()->numberBetween(10_000_000, 900_000_000) : null,
            'deal_closure_note' => $fulfilledAt ? fake()->sentence() : null,
        ];
    }
}
