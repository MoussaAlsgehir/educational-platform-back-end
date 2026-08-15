<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lesson_contents', function (Blueprint $table) {
            // تغيير الحقل من string إلى text لاستيعاب النصوص الطويلة
            $table->text('text_value')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('lesson_contents', function (Blueprint $table) {
            $table->string('text_value')->nullable()->change();
        });
    }
};
