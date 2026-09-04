<?php

namespace Database\Factories;

use App\Models\Country;
use App\Models\HouseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<HouseModel>
 */
class HouseModelFactory extends Factory
{
    protected $model = HouseModel::class;

    public function definition(): array
    {
        $title = 'Modèle ' . fake()->randomElement(['Villa', 'Duplex', 'Appartement', 'Maison']) . ' ' . fake()->numberBetween(1, 99);

        return [
            'country_id' => Country::query()->inRandomOrder()->value('id'),
            'created_by' => User::query()->whereHas('role', fn ($q) => $q->whereIn('slug', ['admin', 'gestionnaire']))->inRandomOrder()->value('id'),
            'title' => $title,
            'slug' => Str::slug($title) . '-' . Str::random(6),
            'short_description' => fake()->optional(0.9)->sentence(),
            'description' => fake()->optional(0.9)->paragraphs(4, true),
            'cover_image' => null,
            'gallery_images' => [],
            'display_order' => fake()->numberBetween(0, 20),
            'is_active' => true,
        ];
    }
}
