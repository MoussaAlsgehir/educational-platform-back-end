<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CourseReview;
use App\Models\User;
use Illuminate\Database\Seeder;

class CourseReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $students = User::whereHas('roles', function ($q) {
            $q->where('name', 'student');
        })->get();

        $courses = Course::all();

        if ($students->isEmpty() || $courses->isEmpty()) {
            $this->command->warn('No students or courses found. Skipping CourseReviewSeeder.');
            return;
        }

        $reviews = [
            [
                'rating' => 5,
                'review_text' => 'Amazing course! The content was very well structured and easy to follow.',
            ],
            [
                'rating' => 4,
                'review_text' => 'Great course overall. Some parts could be more detailed, but very helpful.',
            ],
            [
                'rating' => 5,
                'review_text' => 'Best online course I have ever taken. Highly recommended!',
            ],
            [
                'rating' => 3,
                'review_text' => 'Good introduction, but needs more advanced topics.',
            ],
            [
                'rating' => 4,
                'review_text' => 'Excellent instructor and well-prepared materials.',
            ],
        ];

        foreach ($students as $student) {
            foreach ($courses as $course) {
                $review = $reviews[array_rand($reviews)];

                CourseReview::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'course_id' => $course->id,
                    ],
                    [
                        'rating' => $review['rating'],
                        'review_text' => $review['review_text'],
                    ]
                );
            }
        }

        $this->command->info('Course reviews created successfully.');
    }
}
