<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResource;
use App\Models\Conversation;
use App\Models\Course;
use App\Services\Chat\ConversationService;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\MessageResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ConversationController extends Controller
{
    protected $convService;

    public function __construct(ConversationService $convService)
    {
        $this->convService = $convService;
    }

    // استعراض محادثاتي مع الفلترة حسب النوع (support, complaint, ai_chat)
    public function index(Request $request)
    {
        $query = $request->user()->conversations();

        //  فلترة حسب النوع إذا بعتها
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        //  فلترة حسب النوع الفرعي (support, complaint)
        if ($request->filled('subtype')) {
            $query->where('subtype', $request->subtype);
        }

        $conversations = $query->with('course:id,title', 'activeAdmin:id,first_name,last_name')->latest()->get();

        return ApiResource::sendResponse("Conversations retrieved.", ConversationResource::collection($conversations));
    }

    // جلب شات الكورس الجماعي
    public function getCourseChat(Request $request, $courseId)
    {
        $conversation = Conversation::where('course_id', $courseId)
            ->where('type', 'course_group')
            ->firstOrFail();
        $messages = $conversation->messages()->with('user:id,first_name,last_name,avatar_url')->withCount('likes')->latest('id')->cursorPaginate(20);


        Gate::authorize('view', $conversation);

        return ApiResource::sendResponse("Course chat retrieved.",  MessageResource::collection($messages), 200, $messages->nextPageUrl());
    }

    // إنشاء محادثة دعم (Support)
    public function storeSupport(Request $request)
    {
        $conversation = $this->convService->createSupport($request->user());
        return ApiResource::sendResponse("Support conversation created.", new ConversationResource($conversation->load('activeAdmin')), 201);
    }

    // إنشاء شكوى (Complaint)
    public function storeComplaint(Request $request)
    {
        $request->validate([
            'content' => 'required|string|max:1000',
            'subject_type' => 'nullable|string|in:Message,User,Course,Lesson',
            'subject_id' => 'nullable|integer'
        ]);

        $conversation = $this->convService->createComplaint(
            $request->user(),
            $request->content,
            $request->subject_type,
            $request->subject_id
        );

        return ApiResource::sendResponse("Complaint created.", new ConversationResource($conversation), 201);
    }

    // بدء محادثة الـ AI
    public function startAiChat(Request $request)
    {
        $conversation = $this->convService->createAiChat($request->user());
        return ApiResource::sendResponse("AI Chat is ready.", new ConversationResource($conversation), 201);
    }


    public function messages(Request $request, $id)
    {
        $conversation = Conversation::findOrFail($id);
        Gate::authorize('view', $conversation);

        $messages = $conversation->messages()
            ->with('user:id,first_name,last_name,avatar_url')
            ->withCount('likes')
            ->latest('id')
            ->cursorPaginate(20);

        // تحديث القراءة للطالب
        $this->convService->markAsRead($conversation, $request->user());

        return ApiResource::sendResponse("Messages retrieved.", MessageResource::collection($messages));
    }


    public function pinnedMessages($id)
    {
        $conversation = Conversation::findOrFail($id);
        Gate::authorize('view', $conversation);

        $pinned = $conversation->messages()->where('is_pinned', true)->get();
        return ApiResource::sendResponse("Pinned messages retrieved.", MessageResource::collection($pinned));
    }
}
