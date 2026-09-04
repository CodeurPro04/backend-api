<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    protected $model = ActivityLog::class;

    public function definition(): array
    {
        return [
            'user_id' => fake()->optional(0.85)->passthrough(User::query()->inRandomOrder()->value('id')),
            'action' => fake()->randomElement(['registered', 'login', 'created', 'updated', 'submitted', 'approved', 'rejected']),
            'model_type' => fake()->optional(0.5)->randomElement(['Property', 'SearchRequest', 'InvestmentProject', 'Partnership']),
            'model_id' => null,
            'description' => fake()->optional(0.9)->sentence(),
            'ip_address' => fake()->optional(0.8)->ipv4(),
            'user_agent' => fake()->optional(0.8)->userAgent(),
            'created_at' => now()->subDays(fake()->numberBetween(0, 180)),
        ];
    }
}

