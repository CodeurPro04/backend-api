<?php

namespace Database\Factories;

use App\Models\Property;
use App\Models\PropertyMedia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PropertyMedia>
 */
class PropertyMediaFactory extends Factory
{
    protected $model = PropertyMedia::class;

    public function definition(): array
    {
        $type = fake()->randomElement(['image', 'image', 'image', 'video', 'document']);
        $ext = $type === 'image' ? 'png' : ($type === 'video' ? 'mp4' : 'pdf');
        $name = fake()->slug() . '.' . $ext;

        return [
            'property_id' => Property::query()->inRandomOrder()->value('id'),
            'type' => $type,
            'file_path' => 'seed/' . $name,
            'file_name' => $name,
            'file_size' => fake()->numberBetween(5_000, 3_000_000),
            'mime_type' => $type === 'image'
                ? 'image/png'
                : ($type === 'video' ? 'video/mp4' : 'application/pdf'),
            'order' => 0,
            'is_primary' => false,
        ];
    }
}

