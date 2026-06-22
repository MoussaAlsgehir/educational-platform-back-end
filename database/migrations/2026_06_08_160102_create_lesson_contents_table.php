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
        Schema::create('lesson_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained('lessons')->onDelete('cascade');
            $table->enum('type',['video','pdf','text_article','quiz']) // نوع الوسائط: فيديو , صورة, فيديو مع نص, نص فقط
                ->default('video');
            $table->string('title')->nullable();
            $table->string('text_value')->nullable();//اذا كان نوع الوسائط نصي، يتم تخزين النص هنا
            $table->integer('duration')->nullable(); // مدة الوسائط بالثواني (تستخدم فقط للفيديوهات)
            $table->string('storage_key')->nullable();//مسار الmaster.m3u8 داخل الbuket ب uuid مشفر
            $table->integer('order')->default(1);
            $table->enum('status', [ 'pending', 'processing', 'ready', 'failed'])->default('pending'); // حالة الوسائط: قيد الانتظار, قيد المعالجة, جاهز, فشل
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_contents');
    }
};
