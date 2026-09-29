<?php

namespace App\Enums;

enum IeltsQuestionTypeEnum: string
{
    case MULTIPLE_CHOICE = 'multiple_choice';
    case TRUE_FALSE_NOT_GIVEN = 'true_false_not_given';
    case YES_NO_NOT_GIVEN = 'yes_no_not_given';
    case MATCHING_HEADINGS = 'matching_headings';
    case MATCHING_INFORMATION = 'matching_information';
    case FILL_IN_BLANKS = 'fill_in_blanks';
    case DRAG_DROP = 'drag_drop';
    case MAP_LABELING = 'map_labeling';
    case SHORT_ANSWER = 'short_answer';

    public function label(): string
    {
        return match ($this) {
            self::MULTIPLE_CHOICE => 'Multiple Choice (Trắc nghiệm)',
            self::TRUE_FALSE_NOT_GIVEN => 'True / False / Not Given',
            self::YES_NO_NOT_GIVEN => 'Yes / No / Not Given',
            self::MATCHING_HEADINGS => 'Matching Headings (Nối tiêu đề)',
            self::MATCHING_INFORMATION => 'Matching Information (Nối thông tin)',
            self::FILL_IN_BLANKS => 'Fill in the Blanks / Completion (Điền từ)',
            self::DRAG_DROP => 'Drag & Drop (Kéo thả)',
            self::MAP_LABELING => 'Plan / Map / Diagram Labeling (Gán nhãn bản đồ/sơ đồ)',
            self::SHORT_ANSWER => 'Short Answer Questions (Câu hỏi ngắn)',
        };
    }
}
