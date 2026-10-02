<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ielts_submissions', function (Blueprint $table): void {
            $table->decimal('ai_band_score', 3, 1)->nullable()->after('band_score');
            $table->decimal('teacher_band_score', 3, 1)->nullable()->after('ai_band_score');
            $table->timestamp('teacher_scored_at')->nullable()->after('teacher_band_score');
        });

        DB::table('ielts_submissions')
            ->where('skill', 'writing')
            ->whereNotNull('band_score')
            ->update([
                'ai_band_score' => DB::raw('band_score'),
                'band_score' => null,
            ]);

        // Previous Speaking submissions were incorrectly run through objective answer scoring.
        DB::table('ielts_submissions')
            ->where('skill', 'speaking')
            ->update(['band_score' => null]);
    }

    public function down(): void
    {
        DB::table('ielts_submissions')
            ->where('skill', 'writing')
            ->whereNull('band_score')
            ->whereNotNull('ai_band_score')
            ->update(['band_score' => DB::raw('ai_band_score')]);

        Schema::table('ielts_submissions', function (Blueprint $table): void {
            $table->dropColumn(['teacher_scored_at', 'teacher_band_score', 'ai_band_score']);
        });
    }
};
