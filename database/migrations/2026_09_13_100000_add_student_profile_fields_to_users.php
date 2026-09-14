<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('address')->nullable()->after('role');
            $table->string('college')->nullable()->after('address');
            $table->string('student_group')->nullable()->after('college');
            $table->string('whatsapp')->nullable()->after('student_group');
            $table->string('index_no')->nullable()->after('whatsapp');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['address', 'college', 'student_group', 'whatsapp', 'index_no']);
        });
    }
};
