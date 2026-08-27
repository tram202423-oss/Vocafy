<?php

namespace App\Imports;

use App\Models\Topic;
use App\Models\Vocabulary;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithSkipDuplicates;
use Maatwebsite\Excel\Concerns\WithValidation;

class VocabularyImport implements ToModel, WithHeadingRow, WithValidation, WithSkipDuplicates
{
    /**
     * Map một row Excel thành một Vocabulary model.
     * Trả về null để bỏ qua row (từ trùng lặp).
     */
    public function model(array $row): ?Vocabulary
    {
        $topicId = Topic::where('slug', $row['topic_slug'] ?? '')->value('id');

        // Kiểm tra từ đã tồn tại chưa (cùng word + topic_id)
        $exists = Vocabulary::where('word', $row['word'])
            ->where('topic_id', $topicId)
            ->exists();

        if ($exists) {
            return null; // bỏ qua — không tạo bản ghi trùng
        }

        return new Vocabulary([
            'word'          => $row['word'],
            'pronunciation' => $row['pronunciation'] ?? null,
            'meaning'       => $row['meaning'] ?? null,
            'example'       => $row['example'] ?? null,
            'level'         => $row['level'] ?? 'easy',
            'topic_id'      => $topicId,
        ]);
    }

    public function rules(): array
    {
        return [
            'word'  => 'required|string|max:255',
            'level' => 'nullable|in:easy,medium,hard',
        ];
    }
}
