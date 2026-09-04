<?php

namespace Database\Factories;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Setting>
 */
class SettingFactory extends Factory
{
    protected $model = Setting::class;

    public function definition(): array
    {
        $key = fake()->unique()->slug(2);
        $type = fake()->randomElement(['string', 'number', 'boolean', 'json']);

        $value = match ($type) {
            'number' => (string) fake()->numberBetween(1, 1000),
            'boolean' => fake()->boolean() ? '1' : '0',
            'json' => json_encode(['value' => fake()->word()]),
            default => fake()->sentence(),
        };

        return [
            'key' => $key,
            'value' => $value,
            'type' => $type,
            'description' => fake()->optional(0.6)->sentence(),
        ];
    }
}

