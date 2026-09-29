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
        Schema::create('ielts_band_scores', function (Blueprint $table) {
            $table->id();
            $table->string('skill'); // listening, reading
            $table->string('test_type')->default('academic'); // academic, general_training
            $table->unsignedInteger('raw_score'); // 0..40
            $table->decimal('band_score', 3, 1); // 0.0 - 9.0
            $table->timestamps();

            $table->unique(['skill', 'test_type', 'raw_score']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ielts_band_scores');
    }
};
