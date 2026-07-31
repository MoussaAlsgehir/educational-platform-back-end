<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $studentRole = Role::where('name', 'student')->first();

        $students = [
            [
                'first_name' => 'Ahmed',
                'last_name' => 'Al-Ali',
                'email' => 'ahmed@test.com',
                'phone' => '+963911111111',
                'date_of_birth' => '2000-05-15',
                'education_level' => 'bachelor',
                'avatar_url' => 'avatars/default-avatar.jpg',
                'password' => Hash::make('12345678'),
                'email_verified_at' => now(),
            ],
            [
                'first_name' => 'Sara',
                'last_name' => 'Khaled',
                'email' => 'sara@test.com',
                'phone' => '+963922222222',
                'date_of_birth' => '2001-08-22',
                'education_level' => 'master',
                'avatar_url' => 'avatars/default-avatar.jpg',
                'password' => Hash::make('12345678'),
                'email_verified_at' => now(),
            ],
            [
                'first_name' => 'Mohammad',
                'last_name' => 'Hassan',
                'email' => 'mohammad@test.com',
                'phone' => '+963933333333',
                'date_of_birth' => '1999-12-10',
                'education_level' => 'bachelor',
                'avatar_url' => 'avatars/default-avatar.jpg',
                'password' => Hash::make('12345678'),
                'email_verified_at' => now(),
            ],
            [
                'first_name' => 'Noor',
                'last_name' => 'Ibrahim',
                'email' => 'noor@test.com',
                'phone' => '+963944444444',
                'date_of_birth' => '2002-03-05',
                'education_level' => 'high_school',
                'avatar_url' => 'avatars/default-avatar.jpg',
                'password' => Hash::make('12345678'),
                'email_verified_at' => now(),
            ],
            [
                'first_name' => 'Omar',
                'last_name' => 'Sami',
                'email' => 'omar@test.com',
                'phone' => '+963955555555',
                'date_of_birth' => '2000-07-18',
                'education_level' => 'bachelor',
                'avatar_url' => 'avatars/default-avatar.jpg',
                'password' => Hash::make('12345678'),
                'email_verified_at' => now(),
            ],
        ];

        foreach ($students as $studentData) {
            $user = User::updateOrCreate(
                ['email' => $studentData['email']],
                $studentData
            );

            if ($studentRole) {
                $user->roles()->syncWithoutDetaching([$studentRole->id]);
            }
        }

        $this->command->info('5 students created successfully.');
    }
}
