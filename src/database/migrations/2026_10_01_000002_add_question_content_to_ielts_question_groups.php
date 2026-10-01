<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ielts_question_groups', function (Blueprint $table) {
            $table->longText('question_content')->nullable()->after('passage_content');
        });
    }

    public function down(): void
    {
        Schema::table('ielts_question_groups', function (Blueprint $table) {
            $table->dropColumn('question_content');
        });
    }
};
