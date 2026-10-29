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
        Schema::create('ielts_submissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('ielts_test_id')->nullable()->constrained('ielts_tests')->cascadeOnDelete();
            $table->foreignId('ielts_section_id')->nullable()->constrained('ielts_sections')->cascadeOnDelete();
            $table->string('skill')->nullable(); // listening, reading, writing, speaking, or full
            $table->string('test_type')->default('academic');
            $table->string('status')->default('in_progress'); // in_progress, completed, abandoned
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->unsignedInteger('raw_score')->default(0);
            $table->unsignedInteger('total_questions')->default(40);
            $table->decimal('band_score', 3, 1)->nullable();
            $table->text('examiner_notes')->nullable();
            $table->json('metadata')->nullable(); // tab_switch_count, browser info, etc.
            $table->timestamps();
        });

        Schema::create('ielts_user_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('ielts_submission_id')->constrained('ielts_submissions')->cascadeOnDelete();
            $table->foreignId('ielts_question_id')->constrained('ielts_questions')->cascadeOnDelete();
            $table->text('user_answer')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->boolean('is_flagged_for_review')->default(false);
            $table->unsignedInteger('time_spent_seconds')->default(0);
            $table->text('notes')->nullable(); // candidate in-test notes on this question
            $table->timestamps();

            $table->unique(['ielts_submission_id', 'ielts_question_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ielts_user_answers');
        Schema::dropIfExists('ielts_submissions');
    }
};
