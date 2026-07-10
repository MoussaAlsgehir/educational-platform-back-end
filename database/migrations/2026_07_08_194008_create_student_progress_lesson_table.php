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
        Schema::create('student_progress_lesson', function (Blueprint $table) {
            $table->id();
            // مفاتيح الربط
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('lesson_id')->constrained('lessons')->onDelete('cascade');

            // بيانات التقدم
            $table->integer('watched_seconds')->default(0);
            $table->boolean('is_completed')->default(false);

            $table->timestamps(); // سنحتاج created_at (لأول مرة شاهد فيها) و updated_at (لآخر مرة شاهد فيها)

            // فهرس (Index) لتسريع البحث عن تقدم طالب معين في درس معين
            $table->unique(['student_id', 'lesson_id']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_progress_lesson');
    }
};
