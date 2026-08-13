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

        $currentUser = Auth::user();
        $target = User::find($request->id);

        // Super admin can access any user
        if ($currentUser && $currentUser->hasRole('super_admin')) {
            $user_data = $target->makeHidden(['password', 'email_verified_at', 'remember_token']);
            return ApiResource::sendResponse('Success Get User', $user_data, 200);
        }

        // Admins can access only non-admin users
        if ($currentUser && $currentUser->hasRole('admin')) {
            if ($target->isAdmin()) {
                return ApiResource::sendResponse('Unauthorized to view admin data', null, 403);
            }
            $user_data = $target->makeHidden(['password', 'email_verified_at', 'remember_token']);
            return ApiResource::sendResponse('Success Get User', $user_data, 200);
        }

        // Allow regular users to get their own data only
        if ($currentUser && $currentUser->id === $target->id) {
            $user_data = $target->makeHidden(['password', 'email_verified_at', 'remember_token']);
            return ApiResource::sendResponse('Success Get User', $user_data, 200);
        }

        return ApiResource::sendResponse('Unauthorized', null, 403);
    }


    public function getUsers(Request $request)
    {
        $currentPage = $request->input('page', 1);
        $currentUser = Auth::user();

        // Only admins and super_admin can list users
        if (! $currentUser || (! $currentUser->hasRole('admin') && ! $currentUser->hasRole('super_admin'))) {
            return ApiResource::sendResponse('Unauthorized', null, 403);
        }

        if ($currentUser->hasRole('super_admin')) {
            $users = User::select(['*'])->paginate(5);
        } else {
            // Admins should see only non-admin users (teachers and students)
            $users = User::whereDoesntHave('roles', function ($q) {
                $q->whereIn('name', ['admin', 'super_admin']);
            })->paginate(5);
        }

        $hasPreviousPage = $users->currentPage() > 1;
        $hasNextPage = $users->hasMorePages();

        $usersArray = $users->toArray();
        if (isset($usersArray['links'])) {
            unset($usersArray['links']);
        }

        return ApiResource::sendResponse('Success Get User', [
            'users' => $usersArray,
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

        $currentUser = Auth::user();

        if (! $currentUser || (! $currentUser->hasRole('admin') && ! $currentUser->hasRole('super_admin'))) {
            return ApiResource::sendResponse('Unauthorized', null, 403);
        }

        if ($user) {
            // Admins cannot delete other admins
            if ($currentUser->hasRole('admin') && $user->isAdmin()) {
                return ApiResource::sendResponse('Unauthorized to delete admin user', null, 403);
            }

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
        $currentUser = Auth::user();

        if (! $currentUser || (! $currentUser->hasRole('admin') && ! $currentUser->hasRole('super_admin'))) {
            return ApiResource::sendResponse('Unauthorized', null, 403);
        }

        // Admins cannot change status of admin users
        if ($currentUser->hasRole('admin') && $user->isAdmin()) {
            return ApiResource::sendResponse('Unauthorized to change status of admin', null, 403);
        }

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

        $currentUser = Auth::user();
        if (! $currentUser || (! $currentUser->hasRole('admin') && ! $currentUser->hasRole('super_admin'))) {
            return ApiResource::sendResponse('Unauthorized', null, 403);
        }

        // Admins cannot update admin users
        if ($currentUser->hasRole('admin') && $user->isAdmin()) {
            return ApiResource::sendResponse('Unauthorized to update admin user', null, 403);
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
