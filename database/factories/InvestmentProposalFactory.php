<?php

namespace Database\Factories;

use App\Models\InvestmentProject;
use App\Models\InvestmentProposal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvestmentProposal>
 */
class InvestmentProposalFactory extends Factory
{
    protected $model = InvestmentProposal::class;

    public function definition(): array
    {
        $userId = User::query()->whereHas('role', fn ($q) => $q->whereIn('slug', ['visiteur', 'investisseur']))->inRandomOrder()->value('id');
        $projectId = InvestmentProject::query()->inRandomOrder()->value('id');

        $status = fake()->randomElement(['pending', 'approved', 'rejected']);
        $reviewerId = User::query()->whereHas('role', fn ($q) => $q->whereIn('slug', ['admin', 'gestionnaire']))->inRandomOrder()->value('id');

        return [
            'user_id' => $userId,
            'investment_project_id' => $projectId,
            'amount' => fake()->numberBetween(250_000, 50_000_000),
            'message' => fake()->optional(0.7)->sentence(),
            'status' => $status,
            'reviewed_by' => $status === 'pending' ? null : $reviewerId,
            'reviewed_at' => $status === 'pending' ? null : fake()->dateTimeThisYear(),
            'rejection_reason' => $status === 'rejected' ? fake()->sentence() : null,
        ];
    }
}

