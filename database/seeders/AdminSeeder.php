<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::where('name', 'admin')->first();
        $defaultPassword = Hash::make('12345678');

        $admins = [
            [
                'first_name' => 'System',
                'last_name' => 'Admin',
                'email' => 'admin@test.com',
                'phone' => '+963900000001',
                'date_of_birth' => '1990-01-01',
                'education_level' => 'bachelor',
                'avatar_url' => 'avatars/default-avatar.jpg',
                'current_role' => 'admin',
                'status_account' => 'inActive',
                'password' => $defaultPassword,
                'email_verified_at' => now(),
            ],
            [
                'first_name' => 'Super',
                'last_name' => 'Manager',
                'email' => 'superManagerAdmin@test.com',
                'phone' => '+963900000002',
                'date_of_birth' => '1988-05-10',
                'education_level' => 'master',
                'avatar_url' => 'avatars/default-avatar.jpg',
                'current_role' => 'admin',
                'status_account' => 'inActive',
                'password' => $defaultPassword,
                'email_verified_at' => now(),
            ],
            [
                'first_name' => 'Support',
                'last_name' => 'Manager',
                'email' => 'Supportadmin@test.com',
                'phone' => '+963900222202',
                'date_of_birth' => '1988-05-10',
                'education_level' => 'master',
                'avatar_url' => 'avatars/default-avatar.jpg',
                'current_role' => 'admin',
                'status_account' => 'inActive',
                'password' => $defaultPassword,
                'email_verified_at' => now(),
            ]
        ];

        foreach ($admins as $adminData) {
            $user = User::updateOrCreate(
                ['email' => $adminData['email']],
                $adminData
            );

            if ($adminRole) {
                $user->roles()->syncWithoutDetaching([$adminRole->id]);
            }
        }

        $this->command->info('Admins created successfully.');
    }
}
