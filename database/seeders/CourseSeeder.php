<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Course;
use App\Models\Category;
use App\Models\User;

class CourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = ['programming', 'design', 'marketing', 'business', 'photography','languages','personal_development','data_science'];

        foreach ($categories as $category) {
            Category::create(['name' => $category]);
        }

        Course::factory(15)->create();
        foreach (Course::all() as $course) {
            $course->categories()->attach(rand(1,8));
        }
        foreach (User::all() as $user) {
            $user->roles()->attach([3,4]);
        }

    }
}
