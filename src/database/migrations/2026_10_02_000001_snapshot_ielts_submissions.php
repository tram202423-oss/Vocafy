<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ielts_user_answers', function (Blueprint $table): void {
            $table->dropForeign(['ielts_question_id']);
            $table->unsignedBigInteger('ielts_question_id')->nullable()->change();
            $table->unsignedBigInteger('question_snapshot_id')->nullable()->after('ielts_question_id');
            $table->foreign('ielts_question_id')->references('id')->on('ielts_questions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ielts_user_answers', function (Blueprint $table): void {
            $table->dropForeign(['ielts_question_id']);
            $table->dropColumn('question_snapshot_id');
            // Keep the column nullable after rollback: prior question deletions may have set it to null.
            $table->foreign('ielts_question_id')->references('id')->on('ielts_questions')->cascadeOnDelete();
        });
    }
};
