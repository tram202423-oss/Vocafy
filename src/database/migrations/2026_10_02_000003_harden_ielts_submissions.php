<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ielts_submissions', function (Blueprint $table): void {
            $table->dropForeign(['ielts_test_id']);
            $table->dropForeign(['ielts_section_id']);
            $table->foreign('ielts_test_id')->references('id')->on('ielts_tests')->restrictOnDelete();
            $table->foreign('ielts_section_id')->references('id')->on('ielts_sections')->restrictOnDelete();
            $table->foreignUuid('assigned_examiner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('teacher_scored_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('teacher_criteria')->nullable();
            $table->json('grading_history')->nullable();
            $table->unsignedBigInteger('save_revision')->default(0);
        });
        Schema::table('ielts_questions', fn (Blueprint $table) => $table->string('word_limit_mode', 30)->nullable());
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'ielts.grade', 'guard_name' => 'web']);
    }

    public function down(): void
    {
        Schema::table('ielts_questions', fn (Blueprint $table) => $table->dropColumn('word_limit_mode'));
        Schema::table('ielts_submissions', function (Blueprint $table): void {
            $table->dropForeign(['assigned_examiner_id']);
            $table->dropForeign(['teacher_scored_by']);
            $table->dropColumn(['assigned_examiner_id', 'teacher_scored_by', 'teacher_criteria', 'grading_history', 'save_revision']);
            // Keep RESTRICT: rollback must not reintroduce deletion of exam history.
        });
    }
};
