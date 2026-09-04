<?php

namespace Database\Factories;

use App\Models\Country;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        $roleId = Role::query()->inRandomOrder()->value('id');

        return [
            'country_id' => Country::query()->inRandomOrder()->value('id'),
            'role_id' => $roleId,
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->optional()->e164PhoneNumber(),
            'password' => Hash::make('Password@123'),
            'avatar' => null,
            'interests' => fake()->randomElements(['immobilier', 'construction', 'investissement'], fake()->numberBetween(1, 3)),
            'is_active' => true,
            'email_verified_at' => now(),
            'phone_verified_at' => fake()->optional(0.4)->dateTimeThisYear(),
            'last_login_at' => fake()->optional(0.8)->dateTimeThisMonth(),
        ];
    }

    public function role(string $slug): static
    {
        return $this->state(function () use ($slug) {
            $roleId = Role::query()->where('slug', $slug)->value('id');
            return [
                'role_id' => $roleId,
                'country_id' => in_array($slug, ['admin', 'gestionnaire', 'administrateur'], true)
                    ? null
                    : Country::query()->inRandomOrder()->value('id'),
            ];
        });
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }
}
