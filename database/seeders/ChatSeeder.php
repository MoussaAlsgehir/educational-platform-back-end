<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Course;
use App\Models\User;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageLike;

class ChatSeeder extends Seeder
{
    public function run(): void
    {
        $course = Course::find(1);
        if (!$course) {
            $this->command->warn('Course 1 not found. Run CourseSeeder first.');
            return;
        }

        $teacher = User::find(1); // المدرس
        $student1 = User::find(2); // طالب 1
        $student2 = User::find(3); // طالب 2

        // 1. محادثة الكورس الجماعية (Course Group)
        $groupChat = Conversation::firstOrCreate(
            ['course_id' => $course->id, 'type' => 'course_group'],
            ['status' => 'open']
        );

        $msg1 = Message::create([
            'conversation_id' => $groupChat->id,
            'user_id' => $teacher->id,
            'body' => 'مرحباً بكم في كورس ' . $course->title . '، يمكنكم طرح أسئلتكم هنا.'
        ]);

        $msg2 = Message::create([
            'conversation_id' => $groupChat->id,
            'user_id' => $student1->id,
            'body' => 'أستاذ، هل يمكن شرح الـ WebSockets مرة أخرى؟'
        ]);

        // رسالة فيها رد أستاح ومثبتة (Pinned)
        $msg3 = Message::create([
            'conversation_id' => $groupChat->id,
            'user_id' => $teacher->id,
            'body' => 'تم رفع السيرة الذاتية للمشروع، يرجى مراجعتها.',
            'teacher_reply' => 'تم المراجعة والاعتماد، بالتوفيق!',
            'is_pinned' => true
        ]);

        // إضافة لايك للرسالة الأولى من طالب 2
        MessageLike::firstOrCreate([
            'message_id' => $msg1->id,
            'user_id' => $student2->id
        ]);

        // 2. محادثة الإعلانات (Announcement)
        $announcementChat = Conversation::firstOrCreate(
            ['course_id' => $course->id, 'type' => 'announcement'],
            ['status' => 'open']
        );
        Message::create([
            'conversation_id' => $announcementChat->id,
            'user_id' => $teacher->id,
            'body' => 'إعلان: المحاضرة القادمة يوم الأحد الساعة 10 صباحاً.'
        ]);

        // 3. محادثة دعم (Support)
        $supportChat = Conversation::create(['type' => 'student_admin', 'subtype' => 'support', 'status' => 'open']);
        $supportChat->participants()->attach($student1->id);
        Message::create([
            'conversation_id' => $supportChat->id,
            'user_id' => $student1->id,
            'body' => 'مرحباً، لدي مشكلة في تسجيل الدخول.'
        ]);

        // 4. محادثة شكوى (Complaint) مرتبطة بالكورس
        $complaintChat = Conversation::create([
            'type' => 'student_admin',
            'subtype' => 'complaint',
            'status' => 'open',
            'subject_type' => get_class($course),
            'subject_id' => $course->id
        ]);
        $complaintChat->participants()->attach($student2->id);
        Message::create([
            'conversation_id' => $complaintChat->id,
            'user_id' => $student2->id,
            'body' => 'أريد الإبلاغ عن مشكلة في محتوى هذا الكورس.'
        ]);

        // 5. محادثة مدرس مع إدارة (Teacher Admin)
        $teacherAdminChat = Conversation::create(['type' => 'teacher_admin', 'status' => 'open']);
        $teacherAdminChat->participants()->attach($teacher->id);
        Message::create([
            'conversation_id' => $teacherAdminChat->id,
            'user_id' => $teacher->id,
            'body' => 'مرحباً إدارة، متى يتم تحويل أرباحي؟'
        ]);

        // 6. محادثة الذكاء الاصطناعي (AI Chat)
        $aiChat = Conversation::create(['type' => 'ai_chat', 'status' => 'open']);
        $aiChat->participants()->attach($student1->id);

        Message::create([
            'conversation_id' => $aiChat->id,
            'user_id' => $student1->id,
            'body' => 'كيف أبدأ بتعلم البرمجة؟'
        ]);

        Message::create([
            'conversation_id' => $aiChat->id,
            'user_id' => $student1->id, // user_id هو الطالب دائماً
            'body' => 'مرحباً! أنصحك بالبدء بتعلم أساسيات HTML و CSS.',
            'is_ai_response' => true // رد الذكاء الاصطناعي
        ]);

        $this->command->info('Chat conversations seeded successfully.');
    }
}
