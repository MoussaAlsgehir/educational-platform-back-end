<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\Conversation;
use App\Models\Course;
use App\Models\User;
use App\Models\Category;

class AiService
{
    protected $apiKey;
    protected $model = 'gemini-3.5-flash-lite';

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
    }

    private function getPlatformKnowledge(?User $user): string
    {
        $totalUsers = User::count();
        $totalCourses = Course::where('is_published', true)->count();
        $totalCategories = Category::count();
        $categoriesNames = Category::pluck('name')->implode('، ');

        $knowledge = "=== معلومات منصة LearNova ===\n";
        $knowledge .= "- حالة المنصة: قيد التطوير والتجريب (Beta).\n";
        $knowledge .= "- إحصائيات: {$totalUsers} مستخدم، {$totalCourses} كورس منشور، {$totalCategories} تصنيف.\n";
        $knowledge .= "- التصنيفات: {$categoriesNames}\n";
        $knowledge .= "- نظام النقاط: الشحن يدوي من المشرف المالي بسعر 10 ليرات سورية للنقطة.\n";
        $knowledge .= "- ميزة المعاينة (Preview): كل كورس مدفوع يحتوي على دروس مجانية للمعاينة قبل الشراء.\n";
        $knowledge .= "- صلاحيات الطالب: شراء الكورسات، المشاركة بالشات الجماعي، أداء الكويزات، تتبع التقدم، كتابة مراجعات، فتح تذاكر دعم وشكاوى.\n";
        $knowledge .= "- ميزات تقنية: بث فيديو بدقات متعددة (HLS)، ملاحظات داخل الفيديو، تخزين سحابي آمن.\n\n";

        if ($user) {
            $knowledge .= "=== بيانات المستخدم الحالي ===\n";
            $knowledge .= "- الاسم: {$user->first_name} {$user->last_name}\n";
            $knowledge .= "- الدور: طالب\n\n";
        }

        $courses = Course::where('is_published', true)
            ->whereIn('status', ['active', 'upcoming'])
            ->with(['teacher:id,first_name,last_name', 'categories:id,name', 'sections.lessons' => function ($q) {
                $q->select('id', 'section_id', 'title', 'is_preview');
            }])
            ->withAvg('reviews', 'rating')
            ->get();

        $knowledge .= "=== الكورسات المتاحة ===\n";

        if ($courses->isEmpty()) {
            $knowledge .= "- لا يوجد كورسات منشورة حالياً.\n";
        } else {
            foreach ($courses as $course) {
                $teacherName = $course->teacher ? $course->teacher->first_name . ' ' . $course->teacher->last_name : 'غير محدد';
                $rating = $course->reviews_avg_rating ? round($course->reviews_avg_rating, 1) : 'لا يوجد';
                $courseCats = $course->categories->pluck('name')->implode('، ');
                $previewCount = $course->sections->flatMap->lessons->where('is_preview', true)->count();

                $knowledge .= "كورس: {$course->title}\n";
                $knowledge .= "  - المدرس: {$teacherName} | التصنيف: {$courseCats}\n";
                $knowledge .= "  - السعر: {$course->price} نقطة | التقييم: {$rating}\n";
                $knowledge .= "  - دروس مجانية للمعاينة: {$previewCount}\n";

                if ($course->sections->isNotEmpty()) {
                    $knowledge .= "  - الأقسام:\n";
                    foreach ($course->sections as $section) {
                        $lessonTitles = $section->lessons->pluck('title')->implode('، ');
                        $knowledge .= "    * {$section->title}: {$lessonTitles}\n";
                    }
                }
                $knowledge .= "\n";
            }
        }

        return $knowledge;
    }
     private function getUserStatics(): string
    {

        $docs = \App\Models\LessonContent::where('title', 'System Entity Resolution Guidelines')
            ->where('type', 'text_article')
            ->value('text_value');

        return $docs ? "\n\n" . $docs : '';
    }

        public function generateResponse(Conversation $conversation, string $userMessage, ?User $user): string
    {
        $userFirstName = $user ? $user->first_name : 'الطالب';

        $systemPrompt = "أنت 'نوفا' (Nova)، المساعد الذكي لمنصة LearNova التعليمية.\n";
        $systemPrompt .= "أسلوبك: مهني، طبيعي، سلس، ومباشر. كأنك مستشار تعليمي محترف يتحدث مع صديق.\n";
        $systemPrompt .= "قواعدك:\n";
        $systemPrompt .= "1. خاطب المستخدم باسمه ({$userFirstName}) مرة واحدة عند بداية المحادثة، ثم توقف عن تكرار اسمه.\n";
        $systemPrompt .= "2. لا تسأل المستخدم عن اسمه أبداً، أنت تعرفه مسبقاً.\n";
        $systemPrompt .= "3. كن ودوداً بحدود، دون مبالغة. استخدم الإيموجيز نادراً جداً (حتى 1 بالإجابة).\n";
        $systemPrompt .= "4. أجب على الأسئلة العامة (رياضة، تقنية) بشكل مختصر ومفيد. لا تسوق الكورسات في المواضيع العامة.\n";
        $systemPrompt .= "5. اعتمد حصراً على قاعدة المعرفة للإجابة عن المنصة، ولكن لا تقم بسرد قائمة الكورسات أو الأسعار إلا إذا سأل المستخدم صراحة عنها أو طلب التسجيل.\n";
        $systemPrompt .= "6. إذا اشتكى من قلة المحتوى، وضح أن المنصة قيد التطوير والتجريب (Beta) باحترافية.\n";
        $systemPrompt .= "7. لا تكرر نفس المعلومات أو العبارات في كل رد. كن متنوعاً في إجاباتك.\n";
        $systemPrompt .= "8. إذا قال المستخدم شيئاً غير لائق، تجاهله بمحترافية واطلب منه إعادة صياغة سؤاله دون محاضرات.\n";
        $systemPrompt .= "9. إذا سُئلت عن شيء لا تعرفه خارج المنصة، اعتذر ببساطة: 'لا تتوفر لدي معلومات حول هذا الموضوع'.\n";
        $systemPrompt .= "10. التزم بـ System Compliance Docs المرفقة في نهاية السياق.\n\n";

        $systemPrompt .= $this->getPlatformKnowledge($user);
        $systemPrompt .= $this->getUserStatics();

        $history = $conversation->messages()->latest()->take(10)->get()->reverse();

        $contents = [];
        foreach ($history as $msg) {
            $role = $msg->is_ai_response ? 'model' : 'user';
            $contents[] = [
                'role' => $role,
                'parts' => [['text' => $msg->body]]
            ];
        }

        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $userMessage]]
        ];

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($url, [
            'system_instruction' => [
                'parts' => [['text' => $systemPrompt]]
            ],
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => 0.6, // رفعنا الحرارة شوي عشان يكتب شعر جديد ما يتكرر
                'maxOutputTokens' => 2048,
            ]
        ]);

        if ($response->successful()) {
            $candidates = $response->json('candidates');
            if (!empty($candidates) && isset($candidates[0]['content']['parts'][0]['text'])) {
                return $candidates[0]['content']['parts'][0]['text'];
            }
            \Log::error('Gemini API No Text Response', $response->json());
            return "لم أتمكن من توليد رد مناسب، هل يمكنك إعادة صياغة السؤال؟";
        }

        \Log::error('Gemini API Error', [
            'status' => $response->status(),
            'response' => $response->body()
        ]);

        return "حدث خطأ في الاتصال. حاول مرة أخرى لاحقاً.";
    }
}
