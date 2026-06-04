<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       $user= User::create([
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'email' => 'admin@learnova.com',
            'phone' => '+963900000000',
            'date_of_birth' => '1990-01-01',
            'education_level' => 'bachelor',
            'avatar_url' => '',
            'password' => Hash::make('12345678'),
        ]);

        $defaultRoles = Role::where('name', 'super_admin')->pluck('id')->toArray();
        $user->roles()->attach($defaultRoles);
    }

}
