<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GeminiService
{
    protected string $apiKey;
    protected string $model = 'gemini-flash-lite-latest';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
    }

    /**
     * Sinh câu ví dụ, pronunciation và nghĩa tiếng Việt (kèm từ loại) cho một từ vựng tiếng Anh.
     *
     * @return array{example: string, pronunciation: string, meaning: string}
     */
    public function generateVocabularyExample(string $word, ?string $meaning = null): array
    {
        $contextHint = $meaning
            ? "The word means: \"{$meaning}\"."
            : '';

        $prompt = <<<PROMPT
You are an English-Vietnamese vocabulary assistant.
Given the English word "{$word}". {$contextHint}

Return a JSON object with exactly three keys:
- "example": one clear, natural English sentence using the word (suitable for language learners).
- "pronunciation": the IPA phonetic transcription of the word (e.g. /wɜːrd/).
- "meaning": the Vietnamese meaning of the word including its part of speech abbreviation in parentheses at the end, e.g. "Phương tiện truyền thông (n)" or "Chạy nhanh (v)" or "Đẹp (adj)". Use common Vietnamese. If the word has multiple common meanings, pick the most frequent one.

Respond with ONLY the raw JSON, no markdown, no explanation.
PROMPT;

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post("https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}", [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
            ],
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('Gemini API error: ' . $response->body());
        }

        $text = $response->json('candidates.0.content.parts.0.text');

        $decoded = json_decode(trim($text), true);

        if (json_last_error() !== JSON_ERROR_NONE || ! isset($decoded['example'])) {
            throw new \RuntimeException('Invalid JSON response from Gemini: ' . $text);
        }

        return [
            'example'       => $decoded['example'] ?? '',
            'pronunciation' => $decoded['pronunciation'] ?? '',
            'meaning'       => $decoded['meaning'] ?? '',
        ];
    }

    /**
     * Chấm điểm bài writing bằng AI.
     *
     * @param  string       $topic
     * @param  string       $essay
     * @param  string       $examCategory  ielts_academic|ielts_general|toefl|toeic
     * @param  string       $essayType
     * @param  bool         $hasImage
     * @param  array|null   $imageInput    ['data' => base64, 'mime_type' => string]
     * @return array
     *
     * @throws \RuntimeException
     */
    public function evaluateWriting(
        string $topic,
        string $essay,
        string $examCategory = 'ielts_academic',
        string $essayType = 'ielts_academic_task2',
        bool $hasImage = false,
        ?array $imageInput = null,
    ): array {
        $systemPrompt = $this->buildWritingSystemPrompt($examCategory, $essayType, $hasImage);

        $parts = [];

        if ($hasImage && ! empty($imageInput['data']) && ! empty($imageInput['mime_type'])) {
            $parts[] = [
                'inline_data' => [
                    'mime_type' => $imageInput['mime_type'],
                    'data'      => $imageInput['data'],
                ],
            ];
            $userPrompt = "Topic Prompt: " . ($topic ?: 'See attached chart/diagram image')
                . "\n[Note: An image of the chart/diagram/table has been provided above. Analyze both the image and the prompt when scoring standard and accuracy.]\n\nStudent Essay Submission:\n{$essay}";
        } else {
            $userPrompt = "Topic Prompt: {$topic}\n\nStudent Essay Submission:\n{$essay}";
        }

        $parts[] = ['text' => $userPrompt];

        $response = Http::timeout(60)
            ->retry(2, 500)
            ->post("https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}", [
                'system_instruction' => [
                    'parts' => [
                        ['text' => $systemPrompt],
                    ],
                ],
                'contents' => [
                    ['parts' => $parts],
                ],
                'generationConfig' => [
                    'response_mime_type' => 'application/json',
                    'temperature'        => 0.2,
                ],
            ]);

        if (! $response->successful()) {
            $errorMessage = $response->json('error.message') ?? $response->body();
            throw new \RuntimeException('Gemini API error: ' . $errorMessage);
        }

        $jsonText  = $response->json('candidates.0.content.parts.0.text');
        $resultData = json_decode($jsonText, true);

        if (! $resultData) {
            throw new \RuntimeException('Không thể phân tích dữ liệu từ Gemini: ' . $jsonText);
        }

        return $resultData;
    }

    /**
     * Xây dựng system prompt cho từng loại kỳ thi và loại bài writing.
     */
    private function buildWritingSystemPrompt(string $examCategory, string $essayType, bool $hasImage = false): string
    {
        $task1Or2 = str_contains($essayType, 'task1') ? 'Task Achievement' : 'Task Response';

        $config = match ($examCategory) {
            'toeic' => [
                'exam'     => "TOEIC Writing ({$essayType})",
                'max'      => 200,
                'band'     => 'Level X (Score/200) - Level Name',
                'critMax'  => 5.0,
                'criteria' => ($essayType === 'toeic_email')
                    ? ['Quality & Relevance of Response', 'Grammatical Accuracy & Sentence Variety', 'Business Vocabulary & Professional Tone', 'Organization & Clarity']
                    : ['Support for Opinion & Argumentation', 'Organization & Paragraphing', 'Grammatical Range & Accuracy', 'Business & General Diction'],
            ],
            'toefl' => [
                'exam'     => "TOEFL iBT Writing ({$essayType})",
                'max'      => 30,
                'band'     => 'Score/30 - Performance Level (CEFR)',
                'critMax'  => 5.0,
                'criteria' => ['Content & Task Completion', 'Organization & Coherence', 'Language Use & Academic Grammar', 'Mechanics & Lexical Precision'],
            ],
            'ielts_general' => [
                'exam'     => "IELTS General Writing ({$essayType})",
                'max'      => 9.0,
                'band'     => 'Band X.X - Level Name (CEFR)',
                'critMax'  => 9.0,
                'criteria' => [$task1Or2, 'Coherence & Cohesion', 'Lexical Resource', 'Grammatical Range & Accuracy'],
            ],
            default => [
                'exam'     => "IELTS Academic Writing ({$essayType})",
                'max'      => 9.0,
                'band'     => 'Band X.X - Level Name (CEFR)',
                'critMax'  => 9.0,
                'criteria' => [$task1Or2, 'Coherence & Cohesion', 'Lexical Resource', 'Grammatical Range & Accuracy'],
            ],
        };

        $criteriaSchema = implode(',', array_map(
            fn ($c) => "{\"name\":\"{$c}\",\"score\":0.0,\"maxScore\":{$config['critMax']},\"comment\":\"\"}",
            $config['criteria']
        ));

        $imageInstruction = $hasImage
            ? "\nNote: A chart/diagram image is attached. Check if the essay accurately describes key trends, data points, or steps shown in the image."
            : '';

        return "Act as an official examiner for {$config['exam']} (Max score: {$config['max']}).
Evaluate the student submission strictly against the topic and exam criteria. Output valid JSON ONLY.{$imageInstruction}
Language: comments, reasons, strengths, improvements in concise Vietnamese; original, suggestion, sampleEssay in English.

Constraints:
- strengths: 2-3 concise points.
- improvements: 2-3 actionable points.
- corrections: 3-5 high-impact errors (type: grammar|vocab|style, badge: Ngữ pháp|Từ vựng|Văn phong, concise reason ≤2 sentences).
- sampleEssay: high-scoring model rewrite tailored to topic.

JSON schema:
{
  \"overallScore\": 0.0,
  \"maxScore\": {$config['max']},
  \"bandLevel\": \"{$config['band']}\",
  \"complexity\": \"B2 - C1 Advanced\",
  \"criteria\": [{$criteriaSchema}],
  \"strengths\": [\"\"],
  \"improvements\": [\"\"],
  \"corrections\": [
    {\"paragraph\": 1, \"type\": \"grammar\", \"badge\": \"Ngữ pháp\", \"original\": \"\", \"suggestion\": \"\", \"reason\": \"\"}
  ],
  \"sampleEssay\": \"\"
}";
    }
}
