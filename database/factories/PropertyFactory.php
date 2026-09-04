<?php

namespace Database\Factories;

use App\Models\Property;
use App\Models\PropertyType;
use App\Models\User;
use App\Support\CountryLocations;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Property>
 */
class PropertyFactory extends Factory
{
    protected $model = Property::class;

    public function definition(): array
    {
        $transactionType = fake()->randomElement(['vente', 'location']);
        $price = $transactionType === 'location'
            ? fake()->numberBetween(150_000, 2_500_000)
            : fake()->numberBetween(15_000_000, 850_000_000);

        $status = fake()->randomElement(['draft', 'pending', 'approved', 'rejected']);

        $ownerId = User::query()->whereHas('role', fn ($q) => $q->where('slug', 'proprietaire'))->inRandomOrder()->value('id');
        $agentId = User::query()->whereHas('role', fn ($q) => $q->where('slug', 'agent'))->inRandomOrder()->value('id');
        $validatorId = User::query()->whereHas('role', fn ($q) => $q->where('slug', 'admin'))->inRandomOrder()->value('id');
        $propertyTypeId = PropertyType::query()->inRandomOrder()->value('id');
        $countryId = User::query()->where('id', $ownerId)->value('country_id');
        $place = CountryLocations::forCountryId($countryId);
        $title = fake()->randomElement([
            'Villa moderne',
            'Appartement lumineux',
            'Terrain constructible',
            'Maison familiale',
            'Bureau à louer',
        ]) . ' ' . $place['city'];

        $publishedAt = in_array($status, ['pending', 'approved', 'rejected'], true)
            ? fake()->dateTimeThisYear()
            : null;

        $validatedAt = $status === 'approved' ? fake()->dateTimeThisYear() : null;

        return [
            'country_id' => $countryId,
            'user_id' => $ownerId,
            'agent_id' => fake()->optional(0.65)->passthrough($agentId),
            'property_type_id' => $propertyTypeId,
            'title' => $title,
            'slug' => Str::slug($title) . '-' . Str::random(6),
            'description' => fake()->optional(0.9)->paragraphs(fake()->numberBetween(2, 5), true),
            'agent_comment' => fake()->optional(0.25)->sentence(),
            'transaction_type' => $transactionType,
            'price' => $price,
            'currency' => 'XOF',
            'negotiable' => fake()->boolean(20),
            'surface_area' => fake()->optional(0.8)->randomFloat(2, 25, 450),
            'land_area' => fake()->optional(0.5)->randomFloat(2, 50, 1200),
            'bedrooms' => fake()->optional(0.7)->numberBetween(1, 6),
            'bathrooms' => fake()->optional(0.6)->numberBetween(1, 5),
            'parking_spaces' => fake()->optional(0.5)->numberBetween(0, 4),
            'floor_number' => fake()->optional(0.25)->numberBetween(0, 20),
            'total_floors' => fake()->optional(0.25)->numberBetween(1, 25),
            'year_built' => fake()->optional(0.5)->numberBetween(1990, (int) date('Y')),
            'address' => $place['address'],
            'city' => $place['city'],
            'commune' => $place['commune'],
            'quartier' => $place['quartier'],
            'latitude' => fake()->optional(0.4)->randomFloat(7, 5.20, 5.45),
            'longitude' => fake()->optional(0.4)->randomFloat(7, -4.10, -3.80),
            'status' => $status,
            'rejection_reason' => $status === 'rejected' ? fake()->sentence() : null,
            'featured' => $status === 'approved' ? fake()->boolean(20) : false,
            'views_count' => fake()->numberBetween(0, 2500),
            'published_at' => $publishedAt,
            'validated_at' => $validatedAt,
            'validated_by' => $validatedAt ? $validatorId : null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => 'approved',
            'validated_at' => now(),
        ]);
    }
}
