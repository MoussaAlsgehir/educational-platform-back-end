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
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->nullable()->constrained()->onDelete('cascade'); // لمحادثات الكورس والإعلانات
            $table->enum('type', ['course_group', 'announcement', 'student_admin', 'teacher_admin', 'ai_chat']);
            $table->enum('subtype', ['support', 'complaint'])->nullable(); // فقط لـ student_admin
            $table->enum('status', ['open', 'closed'])->default('open');

            // Admin Lock
            $table->foreignId('active_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('active_at')->nullable();

            // Polymorphic Subject for Complaints
            $table->nullableMorphs('subject'); // subject_type و subject_id

            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
