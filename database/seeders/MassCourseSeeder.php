<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Category;
use App\Models\Course;
use App\Models\Section;
use App\Models\Lesson;
use App\Models\LessonContent;
use App\Models\CourseReview;
use App\Models\CourseDiscount;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class MassCourseSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        // 1. تأمين التصنيفات (6 تصنيفات)
        $categories = [];
        $catNames = ['Web Development', 'Mobile Development', 'Design', 'Marketing', 'Business', 'Photography'];
        foreach ($catNames as $name) {
            $categories[] = Category::firstOrCreate(['name' => $name])->id;
        }

        // 2. تأمين المدرسين والطلاب
        $instructorRole = \App\Models\Role::firstOrCreate(['name' => 'instructor']);
        $studentRole = \App\Models\Role::firstOrCreate(['name' => 'student']);

        $instructors = User::factory()->count(3)->create()->each(function ($user) use ($instructorRole) {
            $user->roles()->attach($instructorRole->id);
        });

        $students = User::factory()->count(10)->create()->each(function ($user) use ($studentRole) {
            $user->roles()->attach($studentRole->id);
        });

        // 3. توليد 50 كورس
        for ($i = 1; $i <= 50; $i++) {
            $publishType = $faker->randomElement(['live', 'on_demand']);
            $courseType = $faker->randomElement(['quiz_based', 'attendance_only']);

            $isLive = $publishType === 'live';
            $startDate = $isLive ? $faker->dateTimeBetween('-1 month', '+1 month') : null;
            $endDate = $isLive ? $faker->dateTimeBetween('+1 month', '+3 months') : null;

            $course = Course::create([
                'teacher_id' => $instructors->random()->id,
                'title' => $faker->sentence(3) . ' ' . $i,
                'description' => $faker->paragraph(3),
                'course_type' => $courseType,
                'publish_type' => $publishType,
                'navigation_type' => $courseType === 'attendance_only' ? 'free' : $faker->randomElement(['free', 'sequential']),
                'price' => $faker->randomElement([0, 100, 150, 200, 250, 300]),
                'status' => $faker->randomElement(['active', 'active', 'active', 'upcoming', 'pending', 'draft']),
                'is_published' => $faker->boolean(80), // 80% منشور
                'cover_image' => 'course_covers/default.png',
                'start_date' => $startDate,
                'end_date' => $endDate,
                'certificate_attendance_threshold' => 60,
                'expected_sections_count' => 1,
            ]);

            // ربط الكورس بـ 1 أو 2 تصنيف
            $course->categories()->attach($faker->randomElements($categories, rand(1, 2)));

            // 4. إضافة محتوى عشوائي (50% يكون فيه محتوى، 50% يكون فاضي)
            if ($faker->boolean(50)) {
                $section = Section::create([
                    'course_id' => $course->id,
                    'title' => 'Introduction to ' . $course->title,
                    'order' => 1,
                ]);

                $lesson = Lesson::create([
                    'section_id' => $section->id,
                    'title' => 'Getting Started',
                    'is_preview' => true,
                    'order' => 1,
                ]);

                LessonContent::create([
                    'lesson_id' => $lesson->id,
                    'type' => 'text_article',
                    'title' => 'Welcome Text',
                    'order' => 1,
                    'status' => 'ready',
                    'text_value' => $faker->paragraph(2),
                ]);

                // 30% احتمال نضيف فيديو وهمي
                if ($faker->boolean(30)) {
                    LessonContent::create([
                        'lesson_id' => $lesson->id,
                        'type' => 'video',
                        'title' => 'Overview Video',
                        'order' => 2,
                        'status' => 'ready',
                        'duration' => $faker->numberBetween(60, 600),
                        'storage_key' => 'videos/lessonContent_22/master.m3u8',
                    ]);
                }
            }

            // 5. إضافة خصم لـ 20% من الكورسات المنشورة
            if ($course->is_published && $faker->boolean(20)) {
                CourseDiscount::create([
                    'course_id' => $course->id,
                    'percentage' => $faker->randomElement([10, 15, 20, 25]),
                    'status' => 'approved',
                    'type' => 'permanent',
                ]);
            }

            // 6. إضافة تقييمات عشوائية للكورسات المنشورة
            if ($course->is_published) {
                $reviewCount = rand(0, 5);
                for ($r = 0; $r < $reviewCount; $r++) {
                    CourseReview::create([
                        'course_id' => $course->id,
                        'student_id' => $students->random()->id,
                        'rating' => $faker->numberBetween(3, 5),
                        'review_text' => $faker->sentence(6),
                    ]);
                }
            }

            // 7. تسجيل طلاب عشوائيين بالكورسات المنشورة
            if ($course->is_published) {
                $enrollCount = rand(0, 5);
                $randomStudents = $students->random($enrollCount);
                foreach ($randomStudents as $student) {
                    $course->students()->syncWithoutDetaching([
                        $student->id => [
                            'attendance_percentage' => rand(0, 100),
                            'is_completed' => $faker->boolean(20)
                        ]
                    ]);
                }
            }
        }
    }
}
