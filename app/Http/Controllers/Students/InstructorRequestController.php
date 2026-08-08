<?php

namespace App\Http\Controllers\Students;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResource;
use App\Models\InstructorRequest;
use Auth;
use Illuminate\Http\Request;
use Storage;

class InstructorRequestController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'specialization' => 'required|string|max:255',
            'cv' => 'required|file|mimes:pdf,doc,docx|max:5120', // حد أقصى 5 ميجا
        ]);

        $user = $request->user();

      
        $existingRequest = InstructorRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->exists();

        if ($existingRequest) {
            return ApiResource::sendResponse("You already have a pending request.", null, 400);
        }

        // رفع السيرة الذاتية على السحابة B2
        $cvPath = $request->file('cv')->store('instructor_cvs', 'b2');

        $instructorRequest = InstructorRequest::create([
            'user_id' => $user->id,
            'specialization' => $request->specialization,
            'cv_url' => $cvPath,
            'status' => 'pending'
        ]);

        return ApiResource::sendResponse("Request submitted successfully.", $instructorRequest, 201);
    }

        // تعديل الطلب (قبل موافقة الإدارة)
    public function update(Request $request, $id)
    {
        $instructorRequest = InstructorRequest::findOrFail($id);


        if ($instructorRequest->user_id !== Auth::id()) {
            return ApiResource::sendResponse("Unauthorized.", null, 403);
        }

        if ($instructorRequest->status !== 'pending') {
            return ApiResource::sendResponse("Cannot modify a processed request.", null, 400);
        }

        $data = $request->validate([
            'specialization' => 'sometimes|string|max:255',
            'cv' => 'sometimes|file|mimes:pdf,doc,docx|max:5120',
        ]);

        if ($request->hasFile('cv')) {
            Storage::disk('b2')->delete($instructorRequest->getRawOriginal('cv_url'));
            $data['cv_url'] = $request->file('cv')->store('instructor_cvs', 'b2');
        }

        $instructorRequest->update($data);

        return ApiResource::sendResponse("Request updated successfully.", $instructorRequest);
    }

    public function destroy($id)
    {
        $instructorRequest = InstructorRequest::findOrFail($id);

        if ($instructorRequest->user_id !== Auth::id()) {
            return ApiResource::sendResponse("Unauthorized.", null, 403);
        }

        if ($instructorRequest->status !== 'pending') {
            return ApiResource::sendResponse("Cannot delete a processed request.", null, 400);
        }

        Storage::disk('b2')->delete($instructorRequest->getRawOriginal('cv_url'));


        $instructorRequest->delete();

        return ApiResource::sendResponse("Request deleted successfully.");
    }
}
