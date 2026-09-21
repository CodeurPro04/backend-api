<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\InvestmentProject;
use App\Models\Partnership;
use App\Models\Property;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Cree/rafraichit un compte de demonstration a mot de passe connu pour
 * chaque role (et chaque specialite d'agent), afin de pouvoir se connecter
 * de facon reproductible pour la capture d'ecran du manuel de formation.
 * Non destructif : utilise updateOrCreate, ne touche a aucune autre donnee.
 */
class FormationAccountsSeeder extends Seeder
{
    private const PASSWORD = 'Formation@2026';

    public function run(): void
    {
        $countryId = Country::query()->where('is_active', true)->value('id');

        $accounts = [
            ['email' => 'formation.gestionnaire@narafgroupe.local', 'role' => 'gestionnaire', 'first_name' => 'Awa', 'last_name' => 'Kone'],
            ['email' => 'formation.agent.immobilier@narafgroupe.local', 'role' => 'agent', 'agent_type' => 'immobilier', 'first_name' => 'Moussa', 'last_name' => 'Diarra'],
            ['email' => 'formation.agent.construction@narafgroupe.local', 'role' => 'agent', 'agent_type' => 'constructeur', 'first_name' => 'Ibrahim', 'last_name' => 'Toure'],
            ['email' => 'formation.agent.investissement@narafgroupe.local', 'role' => 'agent', 'agent_type' => 'investissement', 'first_name' => 'Fatou', 'last_name' => 'Sy'],
            ['email' => 'formation.proprietaire@narafgroupe.local', 'role' => 'proprietaire', 'first_name' => 'Kader', 'last_name' => 'Traore'],
            ['email' => 'formation.visiteur@narafgroupe.local', 'role' => 'visiteur', 'first_name' => 'Aicha', 'last_name' => 'Ba'],
            ['email' => 'formation.investisseur@narafgroupe.local', 'role' => 'investisseur', 'first_name' => 'Jean', 'last_name' => 'Kouassi'],
            ['email' => 'formation.entreprise@narafgroupe.local', 'role' => 'entreprise', 'first_name' => 'Groupe', 'last_name' => 'Baobab'],
        ];

        foreach ($accounts as $account) {
            $role = Role::where('slug', $account['role'])->first();
            if (!$role) {
                $this->command?->warn("Role introuvable: {$account['role']}");
                continue;
            }

            $user = User::updateOrCreate(
                ['email' => $account['email']],
                [
                    'role_id' => $role->id,
                    'first_name' => $account['first_name'],
                    'last_name' => $account['last_name'],
                    'password' => Hash::make(self::PASSWORD),
                    'phone' => '+225 07 01 02 03 04',
                    'country_id' => $countryId,
                    'agent_type' => $account['agent_type'] ?? null,
                    'is_active' => true,
                    'email_verified_at' => now(),
                    'interests' => $account['role'] === 'visiteur' ? ['immobilier', 'construction', 'investissement'] : null,
                ]
            );

            if ($account['role'] === 'entreprise') {
                Partnership::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'country_id' => $countryId,
                        'company_name' => 'Groupe Baobab Immobilier',
                        'company_type' => 'immobilier',
                        'email' => $account['email'],
                        'phone' => $user->phone,
                        'city' => 'Abidjan',
                        'description' => 'Partenaire immobilier de demonstration pour la formation.',
                        'services' => ['Gestion locative', 'Promotion immobiliere'],
                        'status' => 'approved',
                        'approved_at' => now(),
                        'profile_title' => 'Groupe Baobab Immobilier',
                        'profile_description' => 'Compte partenaire de demonstration utilise pour le manuel de formation.',
                    ]
                );
            }

            echo "OK: {$account['email']} / " . self::PASSWORD . "\n";
        }

        // Rattache une propriete et un projet d'investissement existants aux
        // comptes agent/proprietaire de formation, pour avoir des ecrans
        // "detail" non vides sans dupliquer de donnees.
        $ownerFormation = User::where('email', 'formation.proprietaire@narafgroupe.local')->first();
        if ($ownerFormation) {
            Property::query()->where('status', 'approved')->inRandomOrder()->limit(2)
                ->update(['user_id' => $ownerFormation->id]);
        }

        $agentImmo = User::where('email', 'formation.agent.immobilier@narafgroupe.local')->first();
        if ($agentImmo) {
            Property::query()->where('status', 'approved')->whereNull('agent_id')->inRandomOrder()->limit(3)
                ->update(['agent_id' => $agentImmo->id]);
        }
    }
}
