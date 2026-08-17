<?php

namespace App\Http\Controllers\Platform_learnova;

use App\Helpers\ApiResource;
use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminManagementController extends Controller
{
    //

    public function store(Request $request)
    {
        // 1. التحقق من البيانات الأساسية فقط للأدمن
        $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name'  => 'required|string|max:100',
            'email'      => 'required|email|unique:users,email',
            'password'   => 'required|string|min:8',
        ]);

        // 2. إنشاء حساب الأدمن
        $adminUser = User::create([
            'first_name'        => $request->first_name,
            'last_name'         => $request->last_name,
            'email'             => $request->email,
            'password'          => Hash::make($request->password),
            'current_role'      => 'admin',
            'email_verified_at' => now(), // مفعل تلقائياً
        ]);

        $adminRole = Role::where('name', 'admin')->first();
        $adminUser->roles()->attach($adminRole->id);

        return ApiResource::sendResponse("Admin account created successfully.");
    }
}
