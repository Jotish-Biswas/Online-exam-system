<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_explanations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_result_id')->constrained()->onDelete('cascade');
            $table->foreignId('question_id')->constrained()->onDelete('cascade');
            $table->string('answer_fingerprint', 64);
            $table->text('explanation');
            $table->timestamps();

            $table->unique(['exam_result_id', 'question_id', 'answer_fingerprint']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_explanations');
    }
};