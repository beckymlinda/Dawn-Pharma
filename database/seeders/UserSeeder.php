<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create Super Admin User
        $user = User::updateOrCreate(
            [
                'email' => 'ishmaelnyirenda@gmail.com'
            ],
            [
                'name' => 'Ishmael Nyirenda',
                'password' => Hash::make('educ@2026'),
                'email_verified_at' => now(),
            ]
        );

        // Get Super Admin Role
        $superAdminRole = Role::where('name', 'super_admin')->first();

        if ($superAdminRole) {
            $user->roles()->syncWithoutDetaching([
                $superAdminRole->id
            ]);
        }
    }
}