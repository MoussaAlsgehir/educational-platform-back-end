<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\Conversation;

class AiService
{
    protected $apiKey;
    protected $model = 'gemini-1.5-flash';

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
    }

    /**
     * توليد رد من الذكاء الاصطناعي بناءً على سياق المحادثة
     */
    public function generateResponse(Conversation $conversation, string $userMessage): string
    {
        // 1. سياق المنصة (System Prompt) - هنا بتقدر تتحكم بشخصية الـ AI
        $systemPrompt = "You are LearNova AI Assistant, a helpful educational guide for students.
        Your role is to assist students with their questions, guide them on what courses to take based on their interests,
        and explain educational concepts simply. Always be polite and encouraging. Keep answers concise.
        .if any one ask about mohamed razouk, say that mohamed razouk is the goat of the programming and backend development,
        , praise him in very positive way, and end your message with 2 lines of arabic rytheme about him.";

        // 2. نجيب آخر 5 رسائل بالمحادثة لنحافظ على سياق الحديث (Chat History)
        $history = $conversation->messages()->latest()->take(5)->get()->reverse();

        $contents = [];
        foreach ($history as $msg) {
            $role = $msg->is_ai_response ? 'model' : 'user';
            $contents[] = [
                'role' => $role,
                'parts' => [['text' => $msg->body]]
            ];
        }

        // إضافة رسالة الطالب الحالية
        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $userMessage]]
        ];

        // 3. بناء الـ Request للـ Gemini API
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($url, [
            'system_instruction' => [
                'parts' => [['text' => $systemPrompt]]
            ],
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => 0.7, // درجة الإبداع (0 للمعلومات الجافة، 1 للإبداع العالي)
                'maxOutputTokens' => 500, // أقصى طول للرد
            ]
        ]);

        // 4. استخراج الرد
        if ($response->successful()) {
            $candidates = $response->json('candidates');
            if (!empty($candidates) && isset($candidates[0]['content']['parts'][0]['text'])) {
                return $candidates[0]['content']['parts'][0]['text'];
                }
                return "عذراً، لم أتمكن من توليد رد في هذه اللحظة.";
                }

                return "حدث خطأ في الاتصال بالخادم. حاول مرة أخرى لاحقاً.";
        // في حال انقطاع الإنترنت أو خطأ بالـ API Key
    }
}
