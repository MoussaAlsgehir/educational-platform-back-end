<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class InstructorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $instructorRole = Role::where('name', 'instructor')->first();
        $defaultPassword = Hash::make('12345678');

        $instructors = [
            [
                'first_name' => 'Dr. Tariq',
                'last_name' => 'Al-Mansoor',
                'email' => 'tariq.instructor@test.com',
                'phone' => '+963911111111',
                'date_of_birth' => '1985-04-12',
                'education_level' => 'phd',
                'avatar_url' => 'avatars/default-avatar.jpg',
                'current_role' => 'Instructor',
                'status_account' => 'inActive',
                'password' => $defaultPassword,
                'email_verified_at' => now(),
            ],
            [
                'first_name' => 'Eng. Layla',
                'last_name' => 'Al-Hassan',
                'email' => 'layla.instructor@test.com',
                'phone' => '+963922222222',
                'date_of_birth' => '1990-09-15',
                'education_level' => 'master',
                'avatar_url' => 'avatars/default-avatar.jpg',
                'current_role' => 'Instructor',
                'status_account' => 'inActive',
                'password' => $defaultPassword,
                'email_verified_at' => now(),
            ],
            [
                'first_name' => 'Prof. Bilal',
                'last_name' => 'Kassab',
                'email' => 'bilal.instructor@test.com',
                'phone' => '+963933333333',
                'date_of_birth' => '1982-01-20',
                'education_level' => 'phd',
                'current_role' => 'Instructor',
                'status_account' => 'inActive',
                'avatar_url' => 'avatars/default-avatar.jpg',
                'password' => $defaultPassword,
                'email_verified_at' => now(),
            ],
            [
                'first_name' => 'Mona',
                'last_name' => 'Al-Shedid',
                'email' => 'mona.instructor@test.com',
                'phone' => '+963944444444',
                'date_of_birth' => '1988-11-05',
                'education_level' => 'master',
                'avatar_url' => 'avatars/default-avatar.jpg',
                'current_role' => 'Instructor',
                'status_account' => 'inActive',
                'password' => $defaultPassword,
                'email_verified_at' => now(),
            ],
            [
                'first_name' => 'Youssef',
                'last_name' => 'Al-Najjari',
                'email' => 'youssef.instructor@test.com',
                'phone' => '+963955555555',
                'date_of_birth' => '1987-06-30',
                'education_level' => 'master',
                'avatar_url' => 'avatars/default-avatar.jpg',
                'current_role' => 'Instructor',
                'status_account' => 'inActive',
                'password' => $defaultPassword,
                'email_verified_at' => now(),
            ],
        ];

        foreach ($instructors as $instructorData) {
            $user = User::updateOrCreate(
                ['email' => $instructorData['email']],
                $instructorData
            );

            if ($instructorRole) {
                $user->roles()->syncWithoutDetaching([$instructorRole->id]);
            }
        }

        $this->command->info('5 Instructors created successfully.');
    }
}
