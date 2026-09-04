<?php

namespace Database\Factories;

use App\Models\Property;
use App\Models\PropertyRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PropertyRequest>
 */
class PropertyRequestFactory extends Factory
{
    protected $model = PropertyRequest::class;

    public function definition(): array
    {
        $ownerId = User::query()->whereHas('role', fn ($q) => $q->where('slug', 'proprietaire'))->inRandomOrder()->value('id');
        $agentId = User::query()->whereHas('role', fn ($q) => $q->where('slug', 'agent'))->inRandomOrder()->value('id');
        $propertyId = fake()->optional(0.4)->passthrough(Property::query()->inRandomOrder()->value('id'));
        $countryId = $propertyId
            ? Property::query()->where('id', $propertyId)->value('country_id')
            : User::query()->where('id', $ownerId)->value('country_id');

        $status = fake()->randomElement(['pending', 'assigned', 'approved', 'rejected']);
        $assignedAt = $status !== 'pending' ? fake()->dateTimeThisYear() : null;

        return [
            'country_id' => $countryId,
            'user_id' => $ownerId,
            'agent_id' => $assignedAt ? $agentId : null,
            'property_id' => $propertyId,
            'description' => fake()->paragraphs(2, true),
            'status' => $status,
            'rejection_reason' => $status === 'rejected' ? fake()->sentence() : null,
            'approved_at' => $status === 'approved' ? fake()->dateTimeThisYear() : null,
            'rejected_at' => $status === 'rejected' ? fake()->dateTimeThisYear() : null,
            'assigned_at' => $assignedAt,
        ];
    }
}
