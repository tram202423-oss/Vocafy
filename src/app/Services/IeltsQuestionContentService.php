<?php

namespace App\Services;

class IeltsQuestionContentService
{
    /**
     * Content attachments contain a copy of their HTML inside a JSON attribute.
     * Remove that copy before finding [blank_N] or rendering the table.
     */
    public static function forDisplay(?string $html): string
    {
        return preg_replace('/\sdata-trix-attachment=(?:"[^"]*"|\x27[^\x27]*\x27)/u', '', $html ?? '') ?? ($html ?? '');
    }
}
