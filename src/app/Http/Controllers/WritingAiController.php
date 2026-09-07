<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WritingAiController extends Controller
{
    public function index()
    {
        return view('user.writing-ai.index');
    }

    public function evaluate(Request $request)
    {
        $request->validate([
            'topic' => 'nullable|string',
            'essay' => 'required|string|min:15',
            'exam_category' => 'nullable|string',
            'essay_type' => 'nullable|string',
            'image' => 'nullable|array',
            'image.data' => 'nullable|string',
            'image.mime_type' => 'nullable|string',
        ]);

        $topic = $request->input('topic', '');
        $essay = $request->input('essay');
        $imageInput = $request->input('image');

        if (empty($topic) && empty($imageInput['data'])) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng nhập đề bài hoặc tải lên ảnh biểu đồ / sơ đồ đề bài!'
            ], 422);
        }

        $geminiKey = config('services.gemini.api_key') ?: env('GEMINI_API_KEY');

        if (!$geminiKey) {
            return response()->json([
                'success' => false,
                'message' => 'Chưa cấu hình GEMINI_API_KEY trong file .env'
            ], 422);
        }

        $examCategory = $request->input('exam_category', 'ielts_academic');
        $essayType = $request->input('essay_type', 'ielts_academic_task2');

        $hasImage = !empty($imageInput['data']) && !empty($imageInput['mime_type']);
        $systemPrompt = $this->buildSystemPrompt($examCategory, $essayType, $hasImage);

        $parts = [];
        if ($hasImage) {
            $parts[] = [
                'inline_data' => [
                    'mime_type' => $imageInput['mime_type'],
                    'data' => $imageInput['data']
                ]
            ];
            $userPrompt = "Topic Prompt: " . ($topic ?: "See attached chart/diagram image") . "\n[Note: An image of the chart/diagram/table has been provided above. Analyze both the image and the prompt when scoring standard and accuracy.]\n\nStudent Essay Submission:\n{$essay}";
        } else {
            $userPrompt = "Topic Prompt: {$topic}\n\nStudent Essay Submission:\n{$essay}";
        }

        $parts[] = ['text' => $userPrompt];

        try {
            $response = Http::timeout(60)
                ->retry(2, 500)
                ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-lite-latest:generateContent?key={$geminiKey}", [
                    'system_instruction' => [
                        'parts' => [
                            ['text' => $systemPrompt]
                        ]
                    ],
                    'contents' => [
                        [
                            'parts' => $parts
                        ]
                    ],
                    'generationConfig' => [
                        'response_mime_type' => 'application/json',
                        'temperature' => 0.2,
                    ]
                ]);

            if ($response->successful()) {
                $jsonText = $response->json('candidates.0.content.parts.0.text');
                $resultData = json_decode($jsonText, true);

                if ($resultData) {
                    return response()->json([
                        'success' => true,
                        'data' => $resultData
                    ]);
                }
            }

            $errorMessage = $response->json('error.message') ?? 'Không thể phân tích dữ liệu từ Gemini';
            return response()->json([
                'success' => false,
                'message' => 'Lỗi phản hồi từ Gemini API: ' . $errorMessage
            ], 500);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Gemini API Connection Timeout: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Kết nối với Gemini API bị quá thời gian (Timeout). Vui lòng kiểm tra lại mạng hoặc thử nộp bài lại!'
            ], 504);
        } catch (\Exception $e) {
            Log::error('Gemini AI Evaluation Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi kết nối Gemini API: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Build customized System Prompt based on specific exam category & format (Token Optimized)
     */
    private function buildSystemPrompt(string $examCategory, string $essayType, bool $hasImage = false): string
    {
        $task1Or2 = str_contains($essayType, 'task1') ? 'Task Achievement' : 'Task Response';

        $config = match ($examCategory) {
            'toeic' => [
                'exam' => "TOEIC Writing ({$essayType})",
                'max' => 200,
                'band' => 'Level X (Score/200) - Level Name',
                'critMax' => 5.0,
                'criteria' => ($essayType === 'toeic_email')
                    ? ['Quality & Relevance of Response', 'Grammatical Accuracy & Sentence Variety', 'Business Vocabulary & Professional Tone', 'Organization & Clarity']
                    : ['Support for Opinion & Argumentation', 'Organization & Paragraphing', 'Grammatical Range & Accuracy', 'Business & General Diction']
            ],
            'toefl' => [
                'exam' => "TOEFL iBT Writing ({$essayType})",
                'max' => 30,
                'band' => 'Score/30 - Performance Level (CEFR)',
                'critMax' => 5.0,
                'criteria' => ['Content & Task Completion', 'Organization & Coherence', 'Language Use & Academic Grammar', 'Mechanics & Lexical Precision']
            ],
            'ielts_general' => [
                'exam' => "IELTS General Writing ({$essayType})",
                'max' => 9.0,
                'band' => 'Band X.X - Level Name (CEFR)',
                'critMax' => 9.0,
                'criteria' => [$task1Or2, 'Coherence & Cohesion', 'Lexical Resource', 'Grammatical Range & Accuracy']
            ],
            default => [
                'exam' => "IELTS Academic Writing ({$essayType})",
                'max' => 9.0,
                'band' => 'Band X.X - Level Name (CEFR)',
                'critMax' => 9.0,
                'criteria' => [$task1Or2, 'Coherence & Cohesion', 'Lexical Resource', 'Grammatical Range & Accuracy']
            ],
        };

        $criteriaSchema = implode(',', array_map(
            fn($c) => "{\"name\":\"{$c}\",\"score\":0.0,\"maxScore\":{$config['critMax']},\"comment\":\"\"}",
            $config['criteria']
        ));

        $imageInstruction = $hasImage ? "\nNote: A chart/diagram image is attached. Check if the essay accurately describes key trends, data points, or steps shown in the image." : "";

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



