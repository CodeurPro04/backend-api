<?php

namespace Database\Factories;

use App\Models\ConstructionQuote;
use App\Models\QuoteItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuoteItem>
 */
class QuoteItemFactory extends Factory
{
    protected $model = QuoteItem::class;

    public function definition(): array
    {
        $qty = fake()->randomFloat(2, 1, 50);
        $unitPrice = fake()->numberBetween(25_000, 2_500_000);

        return [
            'quote_id' => ConstructionQuote::query()->inRandomOrder()->value('id'),
            'category' => fake()->randomElement(['Gros œuvre', 'Second œuvre', 'Finitions', 'Électricité', 'Plomberie']),
            'description' => fake()->sentence(),
            'quantity' => $qty,
            'unit' => fake()->randomElement(['u', 'm2', 'm3', 'jour', 'lot']),
            'unit_price' => $unitPrice,
            'total_price' => $qty * $unitPrice,
            'order' => 0,
        ];
    }
}

