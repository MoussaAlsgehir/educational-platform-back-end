<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     *Seed the application's database.
     **/
    public function run(): void
    {
        $this->call([
            // 1. الأدوار الأساسية
            RoleSeeder::class,

            // 2. المستخدمون (سوبر أدمن + طلاب)
            SuperAdminSeeder::class,
            StudentSeeder::class,

            // 3. المحتوى التعليمي (كورسات + أقسام + دروس + محتويات)
            CourseSeeder::class,

            // 4. مرفقات الكورسات
            CourseAttachmentSeeder::class,

            // 5. الكويزات والأسئلة والأجوبة
            QuizSeeder::class,

            // 6. تسجيلات الطلاب في الكورسات
            EnrollmentSeeder::class,

            // 7. تقدم الطلاب في الدروس
            LessonProgressSeeder::class,

            // 8. محاولات الطلاب في الكويزات
            StudentAttemptSeeder::class,

            // 9. تقييمات الطلاب للكورسات
            CourseReviewSeeder::class,

            // 10. شهادات PDF فعلية للطلاب الذين أتموا الكورس
            CertificateSeeder::class,

            ChatSeeder::class,

            InstructorSeeder::class
        ]);
    }
}
