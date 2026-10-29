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
        Schema::create('ielts_tests', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('type')->default('academic'); // academic, general_training
            $table->text('description')->nullable();
            $table->unsignedInteger('duration_minutes')->default(150);
            $table->boolean('is_published')->default(false);
            $table->unsignedInteger('total_questions')->default(40);
            $table->timestamps();
        });

        Schema::create('ielts_sections', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('skill'); // listening, reading, writing, speaking
            $table->string('test_type')->default('academic');
            $table->unsignedInteger('time_limit_minutes')->default(60);
            $table->unsignedInteger('total_questions')->default(40);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('ielts_test_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ielts_test_id')->constrained('ielts_tests')->cascadeOnDelete();
            $table->foreignId('ielts_section_id')->constrained('ielts_sections')->cascadeOnDelete();
            $table->unsignedInteger('order')->default(1);
            $table->timestamps();
        });

        Schema::create('ielts_question_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ielts_section_id')->constrained('ielts_sections')->cascadeOnDelete();
            $table->string('title'); // e.g. "Passage 1: The History of Tea" or "Part 1: Phone conversation"
            $table->unsignedInteger('order')->default(1);
            $table->longText('passage_content')->nullable(); // Rich HTML reading passage
            $table->string('audio_url')->nullable(); // Audio source
            $table->longText('transcript')->nullable(); // Audio transcript
            $table->string('question_type')->default('multiple_choice');
            $table->text('instruction')->nullable(); // e.g. "Questions 1-6: Do the following statements agree..."
            $table->string('image_url')->nullable(); // Maps / Charts
            $table->json('settings')->nullable(); // Drag & drop bank, coordinates, options
            $table->timestamps();
        });

        Schema::create('ielts_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ielts_question_group_id')->constrained('ielts_question_groups')->cascadeOnDelete();
            $table->unsignedInteger('question_number'); // 1..40
            $table->unsignedInteger('order')->default(1);
            $table->text('prompt');
            $table->text('explanation')->nullable();
            $table->text('quote_reference')->nullable();
            $table->json('options')->nullable(); // [{"key": "A", "text": "Apple"}, ...]
            $table->text('correct_answer')->nullable(); // "A", "TRUE", or json ["center", "centre"]
            $table->unsignedInteger('word_limit')->nullable();
            $table->unsignedInteger('points')->default(1);
            $table->timestamps();

            $table->index(['ielts_question_group_id', 'question_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ielts_questions');
        Schema::dropIfExists('ielts_question_groups');
        Schema::dropIfExists('ielts_test_sections');
        Schema::dropIfExists('ielts_sections');
        Schema::dropIfExists('ielts_tests');
    }
};
