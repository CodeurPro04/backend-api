<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('slug', 'admin')->first();

        User::create([
            'role_id' => $adminRole->id,
            'first_name' => 'Admin',
            'last_name' => 'System',
            'email' => 'admin@immobilier.com',
            'password' => Hash::make('Admin@123'),
            'phone' => '+225 07 00 00 00 00',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        echo "Admin créé: admin@immobilier.com / Admin@123\n";
    }
}