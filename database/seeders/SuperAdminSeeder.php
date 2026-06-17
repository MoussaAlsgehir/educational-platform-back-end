<?php
namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
/**
* Run the database seeds.
*/
public function run(): void
{
// 1. إنشاء حساب السوبر أدمن
$user = User::create([
'first_name' => 'Super',
'last_name' => 'Admin',
'email' => 'admin@learnova.com',
'phone' => '+963900000000',
'date_of_birth' => '1990-01-01',
'education_level' => 'bachelor',
'avatar_url' => 'avatars/default-avatar.jpg',
'password' => Hash::make('12345678'),
'email_verified_at' => now(), // إضافة اختيارية: تفعيل الحساب تلقائياً لتجنب طلب OTP
]);

// 2. حركة ذكية: تأكد من وجود الدور في الداتابيز أولاً ثم اجلب الـ ID
$role = Role::firstOrCreate(['name' => 'super_admin']);

// 3. ربط الدور بالمستخدم مباشرة عبر الـ ID
$user->roles()->attach($role->id);

    }
}
