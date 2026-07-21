<?php

namespace App\Http\Controllers\Instructors;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResource;
use App\Http\Resources\AttachmentResource;
use App\Models\Course;
use App\Models\CourseAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class CourseAttachmentController extends Controller
{
    /**
     * دالة مساعدة لتركيب رابط السحابة على الملف
     */



    /**
     * عرض المرفقات
     */
    public function show(Request $request, int $attachmentId)
    {
        $attachment = CourseAttachment::findOrFail($attachmentId);
        Gate::authorize('update', $attachment->course);

        return ApiResource::sendResponse("Attachment retrieved successfully.", new AttachmentResource($attachment));
    }

    

    /**
     * رفع مرفق جديد
     */
    public function store(Request $request, int $courseId)
    {
        $course = Course::findOrFail($courseId);
        Gate::authorize('update', $course);

        $request->validate([
            'title' => 'nullable|string|max:255',
            'section_id' => 'nullable|exists:sections,id',
            'type' => 'required|in:pdf,doc,link',
            'file' => 'nullable|required_if:type,pdf,doc|file|mimes:pdf,doc,docx|max:20480',
            'link_url' => 'nullable|required_if:type,link|url'
        ]);

        $fileUrl = null;

        if ($request->type === 'link') {
            $fileUrl = $request->link_url;
        } elseif ($request->hasFile('file')) {
            // الرفع مباشرة على السحابة B2
            $fileUrl = $request->file('file')->store('course_attachments', 'b2');
        }

        $attachment = $course->attachments()->create([
            'section_id' => $request->section_id,
            'title' => $request->title,
            'type' => $request->type,
            'file_url' => $fileUrl
        ]);

        return ApiResource::sendResponse("Attachment added successfully.", new AttachmentResource($attachment), 201);
    }

    /**
     * حذف مرفق
     */
    public function destroy(int $attachmentId)
    {
        $attachment = CourseAttachment::findOrFail($attachmentId);
        Gate::authorize('update', $attachment->course);

        if ($attachment->type !== 'link' && $attachment->file_url) {
            Storage::disk('b2')->delete($attachment->file_url);
        }

        $attachment->delete();

        return ApiResource::sendResponse("Attachment deleted successfully.");
    }
}
