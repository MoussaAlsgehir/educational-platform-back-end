<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->enum('publish_type', ['live', 'on_demand'])->default('on_demand')->after('status');
            $table->boolean('is_published')->default(false)->after('publish_type');
            $table->enum('navigation_type', ['free', 'sequential'])->default('sequential')->after('is_published');
            $table->text('rejection_reason')->nullable()->after('status');
            $table->string('status')->default('draft')->change();
            $table->integer('expected_sections_count')->default(0)->after('navigation_type');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['course_type', 'is_published', 'navigation_type', 'rejection_reason','expected_sections_count']);
            $table->string('status')->default('pending')->change();
        });
    }
};
