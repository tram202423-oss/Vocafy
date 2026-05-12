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
        Schema::create('user_vocabularies', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignUuid('vocabulary_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->integer('mastery_level')->default(0);

            $table->integer('correct_count')->default(0);

            $table->integer('wrong_count')->default(0);

            $table->timestamp('last_reviewed_at')->nullable();

            $table->timestamp('next_review_at')->nullable();

            $table->timestamps();

            $table->unique([
                'user_id',
                'vocabulary_id'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_vocabularies');
    }
};
