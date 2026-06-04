<?php

namespace App\Http\Controllers\Platform_learnova;

use App\Helpers\ApiResource;
use App\Http\Controllers\Controller;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    // جلب جميع الأدوار
    public function index()
    {
        $roles = Role::all();

        return ApiResource::sendResponse("Roles retrieved successfully.", RoleResource::collection($roles));
    }

    // إنشاء دور جديد
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name|max:255',
        ]);

        $role = Role::create([
            'name' => $request->name,
        ]);

        return ApiResource::sendResponse("Role created successfully.", new RoleResource($role), 201);
    }

    // تعديل دور موجود
    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $id,
        ]);

        $role->update([
            'name' => $request->name,
        ]);

        return ApiResource::sendResponse("Role updated successfully.", new RoleResource($role));
    }

    // حذف دور معين
    public function destroy($id)
    {
        $role = Role::findOrFail($id);

        if ($role->users()->exists()) {
            return ApiResource::sendResponse("Cannot delete role. It is currently assigned to one or more users.", null, 400);
        }

        $role->delete();

        return ApiResource::sendResponse("Role deleted successfully.");
    }
}
