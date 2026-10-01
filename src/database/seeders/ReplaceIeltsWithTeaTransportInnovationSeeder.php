<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/** Destructive, opt-in replacement. Never include in DatabaseSeeder. */
class ReplaceIeltsWithTeaTransportInnovationSeeder extends Seeder
{
    public const TABLES = [
        'ielts_user_answers', 'ielts_submissions', 'ielts_test_sections',
        'ielts_answer_options', 'ielts_questions', 'ielts_question_groups',
        'ielts_sections', 'ielts_tests',
    ];

    public function run(): void
    {
        // Fail on malformed input before backing up or deleting any exam data.
        app(IeltsTeaTransportInnovationSeeder::class)->definition();
        DB::transaction(function (): void {
            $backup = ['created_at' => now()->toIso8601String(), 'tables' => []];
            foreach ([...self::TABLES, 'ielts_band_scores'] as $table) {
                $backup['tables'][$table] = DB::table($table)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
            }
            $path = 'backups/ielts/before-tea-transport-innovation-'.now()->format('Ymd-His').'-'.Str::uuid().'.json';
            $json = json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            if (! Storage::disk('local')->put($path, $json)
                || ! hash_equals(hash('sha256', $json), hash('sha256', Storage::disk('local')->get($path)))) {
                throw new RuntimeException('IELTS backup failed; replacement cancelled.');
            }
            $this->command?->info('Private IELTS backup: '.Storage::disk('local')->path($path));
            foreach (self::TABLES as $table) {
                DB::table($table)->delete();
            }
            // Keep users, non-IELTS content and existing band-score configuration.
            $this->call(IeltsTeaTransportInnovationSeeder::class);
            $counts = [];
            foreach (self::TABLES as $table) {
                $counts[$table] = DB::table($table)->count();
            }
            if ($counts['ielts_tests'] !== 1 || $counts['ielts_sections'] !== 1
                || $counts['ielts_question_groups'] !== 7 || $counts['ielts_questions'] !== 40
                || $counts['ielts_submissions'] !== 0 || $counts['ielts_user_answers'] !== 0) {
                throw new RuntimeException('Replacement counts are inconsistent; rolling back.');
            }
            $this->command?->info('Replacement complete: '.json_encode($counts, JSON_THROW_ON_ERROR));
        });
    }
}
