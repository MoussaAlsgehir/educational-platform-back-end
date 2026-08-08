<?php

namespace App\Http\Controllers\Platform_learnova;

use App\Helpers\ApiResource;
use App\Http\Controllers\Controller;
use App\Http\Requests\UsersRequest\UserUpdateRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Auth;
use Illuminate\Http\Request;

class UserController extends Controller
{


    public function getUserById(Request $request)
    {
        $request->validate([
            'id' => ['required', 'integer', 'exists:users,id']
        ]);

        $user_data = User::find($request->id)->except(['password', 'email_verified_at', 'remember_token']);

        return ApiResource::sendResponse('Success Get User', $user_data, 200);
    }


    public function getUsers(Request $request)
    {
        $currentPage = $request->input('page', 1);

        $users = User::select(['*'])->paginate(20);

        $hasPreviousPage = $users->currentPage() > 1;

        $hasNextPage = $users->hasMorePages();

        return ApiResource::sendResponse('Success Get User', [
            'users' => $users->except('links'),
            'has_previous' => $hasPreviousPage,
            'has_next' => $hasNextPage,
            'current_page' => $users->currentPage(),
            'total_pages' => $users->lastPage(),
        ], 200);
    }
    public function destroy(Request $request)
    {
        $request->validate([
            'id' => ['required', 'integer', 'exists:users,id']
        ]);

        $user = User::find($request->id);

        if ($user) {
            $user->delete();
            return ApiResource::sendResponse('User deleted successfully', null, 200);
        } else {
            return ApiResource::sendResponse('User not found', null, 404);
        }
    }

    public function changeStatusUser(Request $request)
    {

        $request->validate([
            'id' => ['required', 'integer', 'exists:users,id'],
            'newStatus' => ['required', 'in:active,inactive,suspend']
        ]);
        $user = User::find($request->id);

        $user->update(['status_account' => $request['newStatus']]);


        return ApiResource::sendResponse('Success Change Status User', null, 200);
    }

    public function update(UserUpdateRequest $request)
    {
        $validated = $request->validated();

        $user = User::find($validated['id']);

        if (!$user) {
            return  ApiResource::sendResponse('User not found', null, 200);
        }

        $updateData = [];

        $allowedFields = ['first_name', 'last_name', 'phone', 'date_of_birth', 'education_level'];

        foreach ($allowedFields as $field) {
            if (isset($validated[$field])) {
                $updateData[$field] = $validated[$field];
            }
        }

        $user->update($updateData);



        return ApiResource::sendResponse('User updated successfully', $user, 200);
    }
}
