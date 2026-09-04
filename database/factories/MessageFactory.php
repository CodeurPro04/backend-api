<?php

namespace Database\Factories;

use App\Models\Message;
use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        $senderId = User::query()->inRandomOrder()->value('id');
        $recipientId = User::query()->where('id', '!=', $senderId)->inRandomOrder()->value('id');

        return [
            'sender_id' => $senderId,
            'recipient_id' => $recipientId,
            'property_id' => fake()->optional(0.6)->passthrough(Property::query()->inRandomOrder()->value('id')),
            'subject' => fake()->optional(0.8)->sentence(),
            'message' => fake()->paragraphs(fake()->numberBetween(1, 3), true),
            'is_read' => fake()->boolean(40),
            'read_at' => null,
            'parent_message_id' => null,
        ];
    }
}

