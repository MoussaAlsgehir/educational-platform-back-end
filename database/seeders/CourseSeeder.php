<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Course;
use App\Models\Section;
use App\Models\Lesson;
use App\Models\LessonContent;
use App\Models\Quizz;
use App\Models\Question;
use App\Models\Answer;
use App\Models\CourseAttachment;
use App\Models\User;
use App\Models\Category;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $category = Category::firstOrCreate(['name' => 'Web Development']);
        $teacher = User::first();

        // 1. إنشاء كورس
        $course = Course::create([
            'teacher_id' => $teacher->id,
            'title' => 'Mastering Full-Stack Development',
            'description' => 'Comprehensive course covering Backend and Frontend integration.',
            'course_type' => 'quiz_based',
            'publish_type' => 'on_demand',
            'navigation_type' => 'sequential',
            'price' => 250,
            'status' => 'active',
            'is_published' => true,
            'cover_image' => 'course_covers/default.png',
            'start_date' => null, // On-Demand ما يحتاج تواريخ
            'end_date' => null,
            'certificate_attendance_threshold' => 80,
            'expected_sections_count' => 1,
        ]);

        $course->categories()->attach($category->id);

        // 2. إنشاء القسم الأول
        $section = Section::create([
            'course_id' => $course->id,
            'title' => 'Introduction & Core Concepts',
            'order' => 1,
        ]);

        // 3. إنشاء المرفق
        CourseAttachment::create([
            'course_id' => $course->id,
            'section_id' => $section->id,
            'title' => 'Section Introduction PDF',
            'type' => 'doc',
            'file_url' => 'course_attachments/NDr8HPMvPf5VERvZLdS00CbNlJ0VIHk9ZrkvO7Dw.docx',
        ]);


        // --- الدرس الأول: فيديو (بريفيو) ---
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
            'status' => 'ready',
            'duration' => 325,
            'storage_key' => 'videos/lessonContent_22/master.m3u8',
        ]);

        // --- الدرس الثاني: مقال نصي + فيديو ---
        $lesson2 = Lesson::create([
            'section_id' => $section->id,
            'title' => '02. Setup Environment and Tools',
            'is_preview' => false,
            'order' => 2,
        ]);

        LessonContent::create([
            'lesson_id' => $lesson2->id,
            'type' => 'video',
            'title' => 'Short Video Playback',
            'order' => 1,
            'status' => 'ready',
            'duration' => 15,
            'storage_key' => 'videos/lessonContent_29/master.m3u8',
        ]);

        LessonContent::create([
            'lesson_id' => $lesson2->id,
            'type' => 'text_article',
            'title' => 'Required Software and Hardware Specifications',
            'order' => 2,
            'status' => 'ready',
            'text_value' => 'In this section, make sure you have PHP 8.2+, Node.js, and VS Code installed. You will also need a local database server like MySQL or SQL Server Express configured.',
        ]);

        // --- الدرس الثالث: مرفق PDF داخل الدرس ---
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
            'storage_key' => 'course_attachments/sRfmX2Mh7EDTxbwSVgOY4WobLbDZDnczGk3Uvcuj.pdf', //  storage_key للـ PDF
        ]);

        // 5. إنشاء الكويز للقسم
        $quiz = Quizz::create([
            'section_id' => $section->id,
            'title' => 'Core Concepts Quiz',
            'passing_score' => 60,
            'order_number' => 1,
        ]);

        $questionsData = [
            [
                'question_text' => 'What does API stand for?',
                'answers' => [
                    ['answer_text' => 'Application Programming Interface', 'is_correct' => true],
                    ['answer_text' => 'Automated Program Integration', 'is_correct' => false],
                    ['answer_text' => 'Advanced Programming Interface', 'is_correct' => false],
                ],
            ],
            [
                'question_text' => 'Which HTTP method is used to create a new resource?',
                'answers' => [
                    ['answer_text' => 'GET', 'is_correct' => false],
                    ['answer_text' => 'POST', 'is_correct' => true],
                    ['answer_text' => 'PUT', 'is_correct' => false],
                ],
            ],
            [
                'question_text' => 'What is the primary purpose of MVC architecture?',
                'answers' => [
                    ['answer_text' => 'Separate concerns into Model, View, Controller', 'is_correct' => true],
                    ['answer_text' => 'Merge all code into one file', 'is_correct' => false],
                    ['answer_text' => 'Improve database performance', 'is_correct' => false],
                ],
            ],
        ];

        foreach ($questionsData as $qData) {
            $question = Question::create([
                'quizz_id' => $quiz->id,
                'question_text' => $qData['question_text'],
                'question_points' => 10,
            ]);

            foreach ($qData['answers'] as $aData) {
                Answer::create([
                    'question_id' => $question->id,
                    'answer_text' => $aData['answer_text'],
                    'is_correct' => $aData['is_correct'],
                ]);
            }
        }
    }
}
