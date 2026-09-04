<?php

namespace Database\Factories;

use App\Models\Partnership;
use App\Models\User;
use App\Support\CountryLocations;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Partnership>
 */
class PartnershipFactory extends Factory
{
    protected $model = Partnership::class;

    public function definition(): array
    {
        $companyType = fake()->randomElement(['immobilier', 'constructeur', 'investisseur']);
        $userId = User::query()->whereHas('role', fn ($q) => $q->where('slug', 'entreprise'))->inRandomOrder()->value('id');
        $approverId = User::query()->whereHas('role', fn ($q) => $q->whereIn('slug', ['admin', 'gestionnaire']))->inRandomOrder()->value('id');
        $countryId = User::query()->where('id', $userId)->value('country_id');
        $place = CountryLocations::forCountryId($countryId);
        $status = fake()->randomElement(['pending', 'approved', 'rejected']);

        return [
            'country_id' => $countryId,
            'user_id' => $userId,
            'company_name' => fake()->company(),
            'company_type' => $companyType,
            'registration_number' => fake()->optional()->bothify('RCI-####-####'),
            'tax_number' => fake()->optional()->bothify('CC-########'),
            'address' => $place['address'],
            'city' => $place['city'],
            'phone' => fake()->optional(0.9)->e164PhoneNumber(),
            'email' => fake()->optional(0.9)->companyEmail(),
            'website' => fake()->optional(0.6)->url(),
            'logo_path' => null,
            'description' => fake()->optional(0.9)->paragraphs(2, true),
            'services' => fake()->optional(0.9)->randomElements(
                ['Étude de faisabilité', 'Construction clé en main', 'Vente de lots', 'Financement', 'Gestion locative'],
                fake()->numberBetween(2, 4)
            ),
            'certifications' => fake()->optional(0.4)->randomElements(
                ['ISO 9001', 'Qualibat', 'BTP Certifié', 'Agrément'],
                fake()->numberBetween(1, 2)
            ),
            'profile_title' => fake()->optional(0.6)->sentence(4),
            'profile_description' => fake()->optional(0.6)->paragraph(),
            'cover_image_path' => null,
            'service_offers' => [],
            'product_showcase' => [],
            'status' => $status,
            'approved_by' => $status === 'approved' ? $approverId : null,
            'approved_at' => $status === 'approved' ? now() : null,
            'rejection_reason' => $status === 'rejected' ? fake()->sentence() : null,
        ];
    }
}
