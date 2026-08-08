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
        Schema::create('instructor_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('specialization'); // التخصص المهني
            $table->string('cv_url'); // مسار السيرة الذاتية المرفوعة على السحابة B2
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable(); // سبب الرفض
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instructor_requests');
    }
};
