<?php

namespace App\Http\Controllers\Students;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResource;
use App\Models\StudentNote;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NoteController extends Controller
{
    /**
     * 1. جلب الملاحظات العامة لدرس معين (Public Notes)
     */
    public function publicNotes(int $lessonId)
    {
        $notes = StudentNote::where('lesson_id', $lessonId)
            ->where('is_private', false)
            ->with('student:id,first_name,last_name,avatar_url')
            ->orderBy('video_timestamp_seconds', 'asc')
            ->get();

        return ApiResource::sendResponse("Public notes retrieved successfully.", $notes);
    }


    public function privateNotes()
    {
        $notes = StudentNote::where('student_id', Auth::id())
            ->where('is_private', true)
            ->orderBy('created_at', 'asc')
            ->get();

        return ApiResource::sendResponse("Private notes retrieved successfully.", $notes);
    }


    public function store(Request $request, int $lessonId)
    {
        $request->validate([
            'note_text' => 'required|string|max:1000',
            'video_timestamp_seconds' => 'required|integer|min:0',
            'is_private' => 'sometimes|boolean',
        ]);

        Lesson::findOrFail($lessonId);

        $note = StudentNote::create([
            'student_id' => Auth::id(),
            'lesson_id' => $lessonId,
            'note_text' => $request->note_text,
            'video_timestamp_seconds' => $request->video_timestamp_seconds,
            'is_private' => $request->has('is_private') ? $request->is_private : true,
        ]);

        return ApiResource::sendResponse("Note added successfully.", $note, 201);
    }

    public function linkToPrivate(int $noteId)
    {
        // نجيب الملاحظة العامة
        $publicNote = StudentNote::where('id', $noteId)->where('is_private', false)->firstOrFail();

        // نتحقق اذا كانت الملاحظة موجودة في دفترك الخاص
        $existingCopy = StudentNote::where('student_id', Auth::id())
            ->where('lesson_id', $publicNote->lesson_id)
            ->where('note_text', $publicNote->note_text)
            ->where('video_timestamp_seconds', $publicNote->video_timestamp_seconds)
            ->exists();

        if ($existingCopy) {
            return ApiResource::sendResponse("This note is already in your private notebook.", null, 400);
        }

        // ننسخها لدفترك الخاص
        $privateNote = StudentNote::create([
            'student_id' => Auth::id(),
            'lesson_id' => $publicNote->lesson_id,
            'note_text' => $publicNote->note_text,
            'video_timestamp_seconds' => $publicNote->video_timestamp_seconds,
            'is_private' => true, // جعلها خاصة
        ]);

        return ApiResource::sendResponse("Note linked to your private notebook.", $privateNote, 201);
    }


    public function update(Request $request,int $noteId)
    {
        $request->validate([
            'note_text' => 'sometimes|required|string|max:1000',
        ]);

        $note = StudentNote::findOrFail($noteId);

        if ($note->student_id !== Auth::id()) {
            return ApiResource::sendResponse("Unauthorized.", null, 403);
        }

        $note->update($request->only('note_text'));

        return ApiResource::sendResponse("Note updated successfully.", $note);
    }


    public function destroy(int$noteId)
    {
        $note = StudentNote::findOrFail($noteId);

        if ($note->student_id !== Auth::id()) {
            return ApiResource::sendResponse("Unauthorized.", null, 403);
        }

        $note->delete();

        return ApiResource::sendResponse("Note deleted successfully.");
    }
}
