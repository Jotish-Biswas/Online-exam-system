<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('academic_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_group_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('name_bn')->nullable();
            $table->string('paper')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['academic_group_id', 'name', 'paper']);
        });

        Schema::create('academic_chapters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_subject_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('title_bn')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('exams', function (Blueprint $table) {
            $table->string('creation_mode')->default('manual')->after('exam_name');
            $table->foreignId('academic_group_id')->nullable()->after('creation_mode')->constrained()->nullOnDelete();
            $table->foreignId('academic_subject_id')->nullable()->after('academic_group_id')->constrained()->nullOnDelete();
        });

        Schema::create('exam_chapter', function (Blueprint $table) {
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_chapter_id')->constrained()->cascadeOnDelete();
            $table->primary(['exam_id', 'academic_chapter_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_chapter');
        Schema::table('exams', function (Blueprint $table) {
            $table->dropForeign(['academic_group_id']);
            $table->dropForeign(['academic_subject_id']);
            $table->dropColumn(['creation_mode', 'academic_group_id', 'academic_subject_id']);
        });
        Schema::dropIfExists('academic_chapters');
        Schema::dropIfExists('academic_subjects');
        Schema::dropIfExists('academic_groups');
    }
};
