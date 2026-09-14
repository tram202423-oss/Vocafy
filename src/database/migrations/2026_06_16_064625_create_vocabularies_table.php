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

            $table->id();

            $table->foreignId('topic_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('word');

            $table->string('pronunciation')->nullable();

            $table->text('meaning');

            $table->text('example')->nullable();

            $table->string('image')->nullable();

            $table->string('audio')->nullable();

            $table->enum('level', [
                'easy',
                'medium',
                'hard'
            ])->default('easy');

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
