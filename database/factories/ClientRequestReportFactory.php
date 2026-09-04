<?php

namespace Database\Factories;

use App\Models\ClientRequest;
use App\Models\ClientRequestReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClientRequestReport>
 */
class ClientRequestReportFactory extends Factory
{
    protected $model = ClientRequestReport::class;

    public function definition(): array
    {
        $agentId = User::query()->whereHas('role', fn ($q) => $q->where('slug', 'agent'))->inRandomOrder()->value('id');

        return [
            'client_request_id' => ClientRequest::query()->inRandomOrder()->value('id'),
            'agent_id' => fake()->optional(0.85)->passthrough($agentId),
            'report_type' => fake()->randomElement(['progress_report', 'final_report']),
            'content' => fake()->paragraphs(fake()->numberBetween(2, 4), true),
            'summary' => fake()->optional(0.7)->sentence(),
            'client_feedback' => fake()->optional(0.4)->sentence(),
            'next_step' => fake()->optional(0.6)->sentence(),
            'sale_price' => fake()->optional(0.2)->randomElement(['150000000', '250000000', '450000000']),
            'closure_note' => fake()->optional(0.2)->sentence(),
            'concluded_at' => fake()->optional(0.2)->dateTimeThisYear(),
        ];
    }
}

