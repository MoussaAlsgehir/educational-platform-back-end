<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('student_enrollments_course', function (Blueprint $table) {
            $table->id();

            // روابط المفاتيح الأجنبية
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');

            // أعمدة بيانات التسجيل
            $table->decimal('attendance_percentage', 5, 2)->default(0.00);
            $table->boolean('is_completed')->default(false);

            // تاريخ التسجيل نفسو created_at

            $table->timestamps();

            // قيد فريد لضمان عدم تكرار تسجيل الطالب في نفس الدورة
            $table->unique(['student_id', 'course_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_enrollments_course');
    }
};
