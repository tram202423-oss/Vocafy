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

            $table->id();

            $table->foreignUuid('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('vocabulary_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->enum('status', [
                'new',
                'learning',
                'mastered'
            ])->default('new');

            $table->integer('review_count')
                ->default(0);

            $table->timestamp('last_review_at')
                ->nullable();

            $table->timestamp('mastered_at')
                ->nullable();

            $table->timestamps();
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
