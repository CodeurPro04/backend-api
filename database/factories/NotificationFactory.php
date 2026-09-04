<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Notification>
 */
class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        $type = fake()->randomElement([
            'property_status',
            'message',
            'search_request',
            'investment_proposal',
            'partnership',
        ]);

        return [
            'user_id' => User::query()->inRandomOrder()->value('id'),
            'type' => $type,
            'title' => fake()->sentence(3),
            'message' => fake()->sentence(),
            'data' => [],
            'is_read' => fake()->boolean(35),
            'read_at' => null,
        ];
    }
}

