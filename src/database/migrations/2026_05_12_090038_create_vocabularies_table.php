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
        Schema::create('vocabularies', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('lesson_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('word');

            $table->string('slug')->unique();

            $table->string('phonetic')->nullable();

            $table->text('meaning');

            $table->text('meaning_en')->nullable();

            $table->text('example')->nullable();

            $table->text('example_vi')->nullable();

            $table->string('audio')->nullable();

            $table->string('image')->nullable();

            $table->enum('difficulty', [
                'easy',
                'medium',
                'hard'
            ])->default('easy');

            $table->integer('view_count')->default(0);

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vocabularies');
    }
};
