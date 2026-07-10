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
        Schema::create('student_certificates_course', function (Blueprint $table) {
            $table->id();

            // الربط مع الطالب والدورة
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');

            // بيانات الشهادة
            $table->string('certificate_url');
            $table->string('serial_number')->unique(); // ضروري للتحقق من الشهادة
            $table->timestamp('issued_at')->useCurrent(); // تاريخ الإصدار الرسمي

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_certificates_course');
    }
};
