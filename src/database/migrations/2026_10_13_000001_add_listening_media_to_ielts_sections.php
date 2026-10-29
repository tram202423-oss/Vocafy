<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ielts_sections', function (Blueprint $table): void {
            $table->string('audio_url')->nullable();
            $table->longText('transcript')->nullable();
        });

        // Preserve media authored on individual groups before the section-level editor existed.
        DB::table('ielts_sections')->where('skill', 'listening')->orderBy('id')->chunkById(100, function ($sections): void {
            foreach ($sections as $section) {
                $groups = DB::table('ielts_question_groups')
                    ->where('ielts_section_id', $section->id)
                    ->orderBy('order')->orderBy('id')
                    ->get(['audio_url', 'transcript']);
                $audio = $groups->first(fn ($group) => trim((string) $group->audio_url) !== '')?->audio_url;
                $transcripts = $groups->pluck('transcript')
                    ->map(fn ($value) => trim((string) $value))
                    ->filter(fn ($value) => $value !== '')
                    ->values();

                DB::table('ielts_sections')->where('id', $section->id)->update([
                    'audio_url' => $audio,
                    'transcript' => $transcripts->isEmpty() ? null : $transcripts->implode("\n\n"),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('ielts_sections', function (Blueprint $table): void {
            $table->dropColumn(['audio_url', 'transcript']);
        });
    }
};
