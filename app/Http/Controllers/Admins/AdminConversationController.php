<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResource;
use App\Models\Conversation;
use App\Services\Chat\ConversationService;
use App\Http\Resources\ConversationResource;
use Illuminate\Http\Request;

class AdminConversationController extends Controller
{
    protected $convService;

    public function __construct(ConversationService $convService)
    {
        $this->convService = $convService;
    }

    // عرض كل المحادثات الخاصة (Support/Complaint) مع الفلترة
    public function index(Request $request)
    {
        $query = Conversation::where('type', $request->input('type', 'student_admin'));

        if ($request->filled('subtype')) {
            $query->where('subtype', $request->subtype);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $conversations = $query->with('participants:id,first_name,last_name', 'subject')->latest()->get();

        return ApiResource::sendResponse("Conversations retrieved.", ConversationResource::collection($conversations));
    }

    // استلام المحادثة (Active Lock)
    public function activate(Request $request, Conversation $conversation)
    {
        try {
            $conversation = $this->convService->activate($conversation, $request->user());
            return ApiResource::sendResponse("Conversation activated.", new ConversationResource($conversation));
        } catch (\Exception $e) {
            return ApiResource::sendResponse($e->getMessage(), null, 422);
        }
    }

    // ترك المحادثة (Release)
    public function release(Request $request, Conversation $conversation)
    {
        try {
            $conversation = $this->convService->release($conversation, $request->user());
            return ApiResource::sendResponse("Conversation released.", new ConversationResource($conversation));
        } catch (\Exception $e) {
            return ApiResource::sendResponse($e->getMessage(), null, 422);
        }
    }

    // إغلاق المحادثة (Close)
    public function close(Conversation $conversation)
    {
        $conversation = $this->convService->close($conversation);
        return ApiResource::sendResponse("Conversation " . $conversation->status . ".", new ConversationResource($conversation));
    }
}
