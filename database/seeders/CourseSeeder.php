<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Course;
use App\Models\Section;
use App\Models\Lesson;
use App\Models\LessonContent; // تأكد من اسم الموديل عندك (LessonContent أو Content)
use App\Models\User;
use App\Models\Category;

class CourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Category::create([
            'name' => 'Web Development',
        ]);
        // جلب مستخدم وأقسام حقيقية من الداتابيز لتجنب ضرب الـ Foreign Keys محلياً
        $teacherId = User::first()?->id ?? 1;
        $categoryId = Category::first()?->id ?? 1;

        // 1. إنشاء كورس أساسي للتجريب
        $course = Course::create([
            'teacher_id' => $teacherId,
            'title' => 'Mastering Full-Stack Development',
            'description' => 'Comprehensive course covering Backend and Frontend integration.',
            'price' => 250,
            'status' => 'active',
            'cover_image' => 'http://127.0.0.1:8000/storage/course_covers/default.png',
            'start_date' => now(),
            'end_date' => now()->addDays(90),
            'certificate_attendance_threshold' => 80,
        ]);

        $course->categories()->attach($categoryId);
        // 2. إنشاء سكشن بقلب الكو

        $section = Section::create([
            'course_id' => $course->id,
            'title' => 'Introduction & Core Concepts',
            'order' => 1,
        ]);

        // 3. الدروس والمحتويات المتنوعة (فيديو، مقال، PDF)

        // --- الدرس الأول: محتوى فيديو ---
        $lesson1 = Lesson::create([
            'section_id' => $section->id,
            'title' => '01. Course Overview & Architecture',
            'is_preview' => true,
            'order' => 1,
        ]);

        LessonContent::create([
            'lesson_id' => $lesson1->id,
            'type' => 'video',
            'title' => 'Intro Video Playback',
            'order' => 1,
            'status' => 'ready', // مهمة جداً عشان دالة الـ Resource ما ترجع null
            'duration' => 325,   // مدة الفيديو بالثواني مثلاً
            'storage_key' => 'videos/lessonContent_22/master.m3u8', // مسار الـ HLS بقلب السحابة
        ]);


        // --- الدرس الثاني: محتوى مقال نصي (Text) ---
        $lesson2 = Lesson::create([
            'section_id' => $section->id,
            'title' => '02. Setup Environment and Tools',
            'is_preview' => false,
            'order' => 2,
        ]);

        LessonContent::create([
            'lesson_id' => $lesson2->id,
            'type' => 'text_article',
            'title' => 'Required Software and Hardware Specifications',
            'order' => 2,
            'status' => 'ready',
            'text_value' => 'In this section, make sure you have PHP 8.2+, Node.js, and VS Code installed. You will also need a local database server like MySQL or SQL Server Express configured.',
        ]);
          LessonContent::create([
            'lesson_id' => $lesson2->id,
            'type' => 'video',
            'title' => 'Short Video Playback',
            'order' => 1,
            'status' => 'ready', // مهمة جداً عشان دالة الـ Resource ما ترجع null
            'duration' => 15,   // مدة الفيديو بالثواني مثلاً
            'storage_key' => '/videos/lessonContent_29/master.m3u8', // مسار الـ HLS بقلب السحابة
        ]);



        // --- الدرس الثالث: محتوى ملف مرفق (PDF) ---
        $lesson3 = Lesson::create([
            'section_id' => $section->id,
            'title' => '03. Full Cheat-Sheet Guide',
            'is_preview' => false,
            'order' => 3,
        ]);

        LessonContent::create([
            'lesson_id' => $lesson3->id,
            'type' => 'pdf',
            'title' => 'Download Course Syllabus PDF',
            'order' => 1,
            'status' => 'ready',
            // حالياً المسار محلي، الفرونت حيشوفه asset('storage/attachments/syllabus.pdf')
            'storage_key' => 'attachments/syllabus.pdf',
        ]);
    }
}
