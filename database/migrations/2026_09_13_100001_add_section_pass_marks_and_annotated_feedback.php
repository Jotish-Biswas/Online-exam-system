<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->decimal('mcq_pass_percentage', 5, 2)->nullable()->after('pass_percentage');
            $table->decimal('writing_pass_percentage', 5, 2)->nullable()->after('mcq_pass_percentage');
        });

        Schema::table('student_answers', function (Blueprint $table) {
            $table->string('annotated_file_path')->nullable()->after('file_mime_type');
            $table->string('annotated_original_filename')->nullable()->after('annotated_file_path');
            $table->timestamp('feedback_released_at')->nullable()->after('is_graded');
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn(['mcq_pass_percentage', 'writing_pass_percentage']);
        });

        Schema::table('student_answers', function (Blueprint $table) {
            $table->dropColumn([
                'annotated_file_path',
                'annotated_original_filename',
                'feedback_released_at',
            ]);
        });
    }
};
