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
        // 1. Users table additions
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('name');
            $table->string('role')->default('teacher')->after('password');
        });

        // 2. Exams table additions
        Schema::table('exams', function (Blueprint $table) {
            $table->decimal('negative_marking', 4, 2)->default(0.00)->after('enable_anti_cheating');
            $table->decimal('pass_percentage', 5, 2)->default(40.00)->after('negative_marking');
            $table->dateTime('start_time')->nullable()->after('pass_percentage');
            $table->dateTime('end_time')->nullable()->after('start_time');
            $table->foreignId('created_by')->nullable()->after('end_time')->constrained('users')->nullOnDelete();
        });

        // 3. Questions table additions
        Schema::table('questions', function (Blueprint $table) {
            $table->decimal('marks', 4, 2)->default(1.00)->after('question_type');
            $table->text('explanation')->nullable()->after('file_upload_settings');
        });

        // 4. Exam results table modifications
        Schema::table('exam_results', function (Blueprint $table) {
            $table->decimal('total_marks', 6, 2)->nullable()->after('total_questions');
            $table->decimal('score', 6, 2)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exam_results', function (Blueprint $table) {
            $table->dropColumn('total_marks');
            $table->integer('score')->change();
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn(['marks', 'explanation']);
        });

        Schema::table('exams', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn([
                'negative_marking',
                'pass_percentage',
                'start_time',
                'end_time',
                'created_by'
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'role']);
        });
    }
};
