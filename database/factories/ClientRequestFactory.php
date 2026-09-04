<?php

namespace Database\Factories;

use App\Models\ClientRequest;
use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClientRequest>
 */
class ClientRequestFactory extends Factory
{
    protected $model = ClientRequest::class;

    public function definition(): array
    {
        $userId = User::query()->whereHas('role', fn ($q) => $q->where('slug', 'visiteur'))->inRandomOrder()->value('id');
        $agentId = User::query()->whereHas('role', fn ($q) => $q->where('slug', 'agent'))->inRandomOrder()->value('id');
        $propertyId = fake()->optional(0.6)->passthrough(Property::query()->inRandomOrder()->value('id'));
        $countryId = $propertyId
            ? Property::query()->where('id', $propertyId)->value('country_id')
            : User::query()->where('id', $userId)->value('country_id');

        $status = fake()->randomElement(['pending', 'assigned', 'approved', 'rejected']);
        $assignedAt = $status !== 'pending' ? fake()->dateTimeThisYear() : null;

        return [
            'country_id' => $countryId,
            'user_id' => $userId,
            'property_id' => $propertyId,
            'agent_id' => $assignedAt ? $agentId : null,
            'name' => fake()->name(),
            'email' => fake()->optional(0.9)->safeEmail(),
            'phone' => fake()->optional(0.9)->e164PhoneNumber(),
            'message' => fake()->paragraph(),
            'status' => $status,
            'deal_status' => $status === 'approved' ? fake()->randomElement(['won', 'lost']) : null,
            'rejection_reason' => $status === 'rejected' ? fake()->sentence() : null,
            'approved_at' => $status === 'approved' ? fake()->dateTimeThisYear() : null,
            'rejected_at' => $status === 'rejected' ? fake()->dateTimeThisYear() : null,
            'assigned_at' => $assignedAt,
            'deal_concluded_at' => $status === 'approved' ? fake()->dateTimeThisYear() : null,
            'deal_sale_price' => $status === 'approved' ? (string) fake()->numberBetween(10_000_000, 900_000_000) : null,
            'deal_closure_note' => $status === 'approved' ? fake()->sentence() : null,
        ];
    }
}
