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
        Schema::create('course_discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->decimal('percentage', 5, 2); 
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->enum('type', ['permanent', 'limited'])->default('permanent'); // دائم أو لفترة محددة
            $table->timestamp('starts_at')->nullable(); // بداية الخصم (إذا limited)
            $table->timestamp('ends_at')->nullable();   // نهاية الخصم (إذا limited)
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_discounts');
    }
};
