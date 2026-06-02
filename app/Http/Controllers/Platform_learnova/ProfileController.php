<?php

namespace App\Http\Controllers\Platform_learnova;

use App\Helpers\ApiResource;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileRequest\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * عرض بيانات الملف الشخصي للمستخدم عبر الـ ID
     */
    public function show($id)
    {
        $user = User::find($id);

        if (!$user) {
            return ApiResource::sendResponse("User not found.", null, 200);
        }

        if ($user->id !== Auth::id()) {
            return ApiResource::sendResponse("Unauthorized access to user profile.", null, 403);
        }

        return ApiResource::sendResponse("User profile retrieved successfully.", ['user_information' => new UserResource($user)]);
    }

    /**
     * تحديث بيانات الملف الشخصي وتعديل الصورة الشخصية عبر الـ ID
     */
    public function update(UpdateProfileRequest $request, $id)
    {
        if (empty($request->all())) {
            return ApiResource::sendResponse("No data provided for update.", null, 400);
        }

        $user = User::find($id);

        if (!$user) {
            return ApiResource::sendResponse("User not found.", null, 200);
        }

        if ($user->id !== Auth::id()) {
            return ApiResource::sendResponse("Unauthorized access to user profile.", null, 403);
        }

        $data_validated = $request->validated();

        if ($request->hasFile('avatar_url')) {
            $oldAvatarPath = $user->getRawOriginal('avatar_url');

            if ($oldAvatarPath && $oldAvatarPath !== 'avatars/default-avatar.jpg' && Storage::disk('public')->exists($oldAvatarPath)) {
                Storage::disk('public')->delete($oldAvatarPath);
            }

            $file = $request->file('avatar_url');
            $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('avatars', $fileName, 'public');

            $data_validated['avatar_url'] = $path;
        } elseif ($request->exists('avatar_url') && $request->input('avatar_url') === null) {
            $oldAvatarPath = $user->getRawOriginal('avatar_url');

            if ($oldAvatarPath && $oldAvatarPath !== 'avatars/default-avatar.jpg' && Storage::disk('public')->exists($oldAvatarPath)) {
                Storage::disk('public')->delete($oldAvatarPath);
            }

            $data_validated['avatar_url'] = 'avatars/default-avatar.jpg';
        } else {
            unset($data_validated['avatar_url']);
        }

        $user->update($data_validated);

        return ApiResource::sendResponse("User profile updated successfully.", ['user_information' => new UserResource($user)]);
    }
}
