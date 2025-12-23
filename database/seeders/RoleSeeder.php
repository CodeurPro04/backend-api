<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Administrateur',
                'slug' => 'admin',
                'description' => 'Accès complet au système',
                'permissions' => json_encode(['all']),
            ],
            [
                'name' => 'Gestionnaire',
                'slug' => 'gestionnaire',
                'description' => 'Gestion des annonces et assignations',
                'permissions' => json_encode(['manage_properties', 'assign_agents']),
            ],
            [
                'name' => 'Agent Immobilier',
                'slug' => 'agent',
                'description' => 'Validation des annonces et gestion des clients',
                'permissions' => json_encode(['validate_properties', 'manage_messages']),
            ],
            [
                'name' => 'Propriétaire',
                'slug' => 'proprietaire',
                'description' => 'Création et gestion de ses annonces',
                'permissions' => json_encode(['create_properties']),
            ],
            [
                'name' => 'Visiteur',
                'slug' => 'visiteur',
                'description' => 'Consultation et demandes',
                'permissions' => json_encode(['view_properties', 'send_messages']),
            ],
            [
                'name' => 'Investisseur',
                'slug' => 'investisseur',
                'description' => 'Propositions d\'investissement',
                'permissions' => json_encode(['view_investments', 'propose_investment']),
            ],
            [
                'name' => 'Entreprise Partenaire',
                'slug' => 'entreprise',
                'description' => 'Partenariats construction/services',
                'permissions' => json_encode(['view_partnerships']),
            ],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}