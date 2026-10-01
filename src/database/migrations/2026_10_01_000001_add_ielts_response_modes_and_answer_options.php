<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ielts_question_groups', function (Blueprint $table) {
            $table->string('response_mode')->default('standard')->after('question_type');
            $table->string('option_usage')->default('repeat')->after('response_mode');
        });

        Schema::table('ielts_questions', function (Blueprint $table) {
            $table->decimal('drop_x', 5, 2)->nullable()->after('points');
            $table->decimal('drop_y', 5, 2)->nullable()->after('drop_x');
        });

        Schema::create('ielts_answer_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ielts_question_group_id')->constrained('ielts_question_groups')->cascadeOnDelete();
            $table->string('option_key');
            $table->text('label');
            $table->unsignedInteger('order')->default(1);
            $table->timestamps();
            $table->unique(['ielts_question_group_id', 'option_key']);
        });

        DB::table('ielts_question_groups')
            ->where('question_type', 'drag_drop')
            ->orderBy('id')
            ->chunkById(100, function ($groups): void {
                foreach ($groups as $group) {
                    $settings = json_decode($group->settings ?? '{}', true) ?: [];
                    $usage = ($settings['drag_option_usage'] ?? 'repeat') === 'once' ? 'once' : 'repeat';

                    DB::table('ielts_question_groups')
                        ->where('id', $group->id)
                        ->update(['response_mode' => 'drag_drop', 'option_usage' => $usage]);

                    $options = $settings['drag_options'] ?? [];
                    if (empty($options)) {
                        $questions = DB::table('ielts_questions')
                            ->where('ielts_question_group_id', $group->id)
                            ->orderBy('question_number')
                            ->get(['options']);
                        $options = $questions->flatMap(fn ($question) => json_decode($question->options ?? '[]', true) ?: [])->all();
                    }

                    $seen = [];
                    foreach ($options as $index => $option) {
                        $key = trim((string) ($option['key'] ?? ''));
                        $label = trim((string) ($option['text'] ?? ''));
                        if ($key === '' || $label === '' || isset($seen[$key])) {
                            continue;
                        }

                        $seen[$key] = true;
                        DB::table('ielts_answer_options')->insert([
                            'ielts_question_group_id' => $group->id,
                            'option_key' => $key,
                            'label' => $label,
                            'order' => $index + 1,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('ielts_answer_options');

        Schema::table('ielts_questions', function (Blueprint $table) {
            $table->dropColumn(['drop_x', 'drop_y']);
        });

        Schema::table('ielts_question_groups', function (Blueprint $table) {
            $table->dropColumn(['response_mode', 'option_usage']);
        });
    }
};
