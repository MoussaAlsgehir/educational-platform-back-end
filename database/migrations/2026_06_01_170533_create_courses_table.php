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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('course_type', ['quiz_based', 'attendance_only'])->default('quiz_based');//نوع الدورة: دورة تعتمد على الاختبارات أو دورة تعتمد فقط على الحضور
            $table->integer('certificate_attendance_threshold')->default(60);//نسبة الحضور المطلوبة للحصول على الشهادة
            $table->decimal('price', 10, 2)->default(0.00);
            $table->string('status')->default('pending'); // pending, active, rejected,upcoming ,completed
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();//تاريخ الدورة يحدد فترة تفاعل الاستاذ مع الطلاب
            $table->timestamps();
            $table->unique(['teacher_id', 'title']); // ضمان عدم تكرار العنوان لنفس المعلم
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
