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
        Schema::table('exams', function (Blueprint $table) {
            $table->integer('duration_minutes')->nullable()->default(30)->after('description');
            $table->boolean('shuffle_questions')->default(false)->after('duration_minutes');
            $table->boolean('shuffle_options')->default(false)->after('shuffle_questions');
            $table->boolean('enable_anti_cheating')->default(true)->after('shuffle_options');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn([
                'duration_minutes',
                'shuffle_questions',
                'shuffle_options',
                'enable_anti_cheating'
            ]);
        });
    }
};
