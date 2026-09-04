<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdminCommand extends Command
{
    /**
     * php artisan admin:create
     * php artisan admin:create --email=admin@africabuildinvest.com --first-name=Admin --last-name=ABI --phone="+225 0000000000" --password="SecretPass123"
     */
    protected $signature = 'admin:create
        {--email= : Adresse email du compte admin}
        {--first-name= : Prénom}
        {--last-name= : Nom}
        {--phone= : Téléphone}
        {--password= : Mot de passe (sinon demandé de façon masquée)}';

    protected $description = "Crée (ou met à jour) un compte administrateur";

    public function handle(): int
    {
        $adminRole = Role::where('slug', 'admin')->first();

        if (!$adminRole) {
            $this->error("Le rôle 'admin' n'existe pas en base.");
            $this->line("Lancez d'abord : php artisan db:seed --class=RoleSeeder");
            return self::FAILURE;
        }

        $email = $this->option('email') ?: $this->ask("Email de l'administrateur");
        $firstName = $this->option('first-name') ?: $this->ask('Prénom', 'Admin');
        $lastName = $this->option('last-name') ?: $this->ask('Nom', 'ABI');
        $phone = $this->option('phone') ?: $this->ask('Téléphone (optionnel)', '');

        $password = $this->option('password');
        if (!$password) {
            $password = $this->secret('Mot de passe (min. 8 car., majuscule, minuscule, chiffre)');
            $confirmation = $this->secret('Confirmez le mot de passe');
            if ($password !== $confirmation) {
                $this->error('Les mots de passe ne correspondent pas.');
                return self::FAILURE;
            }
        }

        $validator = Validator::make(
            [
                'email' => $email,
                'password' => $password,
            ],
            [
                'email' => 'required|email',
                'password' => ['required', Password::min(8)->mixedCase()->numbers()],
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return self::FAILURE;
        }

        $existing = User::withTrashed()->where('email', $email)->first();

        if ($existing) {
            if (!$this->confirm("Un compte existe déjà pour {$email}. Le transformer en admin et remplacer son mot de passe ?", false)) {
                $this->line('Opération annulée.');
                return self::SUCCESS;
            }

            $existing->forceFill([
                'role_id' => $adminRole->id,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $phone ?: $existing->phone,
                'password' => Hash::make($password),
                'is_active' => true,
                'email_verified_at' => $existing->email_verified_at ?? now(),
                'deleted_at' => null,
            ])->save();

            $this->info("Compte admin mis à jour : {$email}");
            return self::SUCCESS;
        }

        User::create([
            'role_id' => $adminRole->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'phone' => $phone ?: null,
            'password' => Hash::make($password),
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->info("Compte admin créé avec succès : {$email}");
        return self::SUCCESS;
    }
}
