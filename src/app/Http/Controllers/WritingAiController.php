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
            'topic' => 'required|string|min:10',
            'essay' => 'required|string|min:15',
            'exam_category' => 'nullable|string',
            'essay_type' => 'nullable|string',
        ]);

        $geminiKey = config('services.gemini.api_key') ?: env('GEMINI_API_KEY');

        if (!$geminiKey) {
            return response()->json([
                'success' => false,
                'message' => 'Chưa cấu hình GEMINI_API_KEY trong file .env'
            ], 422);
        }

        $topic = $request->input('topic');
        $essay = $request->input('essay');
        $examCategory = $request->input('exam_category', 'ielts_academic');
        $essayType = $request->input('essay_type', 'ielts_academic_task2');

        $systemPrompt = $this->buildSystemPrompt($examCategory, $essayType);
        $userPrompt = "Đề bài (Prompt):\n{$topic}\n\nBài làm của học viên (Student Essay):\n{$essay}";

        try {
            $response = Http::timeout(60)
                ->retry(2, 500)
                ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-lite-latest:generateContent?key={$geminiKey}", [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $systemPrompt . "\n\n" . $userPrompt]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'response_mime_type' => 'application/json',
                        'temperature' => 0.3,
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
     * Build customized System Prompt based on specific exam category & format
     */
    private function buildSystemPrompt(string $examCategory, string $essayType): string
    {
        $examSpecificRules = "";

        if ($examCategory === 'toeic') {
            $examSpecificRules = "EXAM TYPE: TOEIC Writing (Format: {$essayType}).
SCORING RULES & SCALE:
- overallScore: Set total TOEIC Writing score out of 200 (e.g., 140, 160, 170, 180, 190).
- maxScore: 200.
- bandLevel: E.g., 'Level 7 (170/200 điểm) - B2 High Professional'.
- CRITERIA (Provide 4 criteria with maxScore 5.0):
  If format is 'toeic_email' (Trả lời Email):
    1. 'Quality & Relevance of Response' (Trả lời đủ yêu cầu)
    2. 'Grammatical Accuracy & Sentence Variety' (Cấu trúc & Ngữ pháp)
    3. 'Business Vocabulary & Professional Tone' (Từ vựng công sở & Văn phong)
    4. 'Organization & Clarity' (Bố cục & Rõ ràng)
  If format is 'toeic_essay' (Opinion Essay):
    1. 'Support for Opinion & Argumentation' (Luận điểm & Thuyết phục)
    2. 'Organization & Paragraphing' (Bố cục & Mạch lạc)
    3. 'Grammatical Range & Accuracy' (Cấu trúc & Chính tả)
    4. 'Business & General Diction' (Từ vựng xã hội & Công sở)";
        } elseif ($examCategory === 'toefl') {
            $examSpecificRules = "EXAM TYPE: TOEFL iBT Writing (Format: {$essayType}).
SCORING RULES & SCALE:
- overallScore: Set overall scaled score out of 30 (e.g., 22, 25, 27, 28).
- maxScore: 30.
- bandLevel: E.g., '25 / 30 điểm - High Performance (C1 Academic)'.
- CRITERIA (Provide 4 criteria with maxScore 5.0):
  1. 'Content & Task Completion' (Nội dung & Đủ ý bài học thuật)
  2. 'Organization & Coherence' (Tổ chức ý & Tính mạch lạc)
  3. 'Language Use & Academic Grammar' (Sử dụng ngữ pháp học thuật)
  4. 'Mechanics & Lexical Precision' (Chính tả, dấu câu & Từ vựng)";
        } elseif ($examCategory === 'ielts_general') {
            $task1Or2 = ($essayType === 'ielts_general_task1') ? 'Task Achievement' : 'Task Response';
            $examSpecificRules = "EXAM TYPE: IELTS General Training (Format: {$essayType}).
SCORING RULES & SCALE:
- overallScore: Set IELTS Band Score from 1.0 to 9.0 with 0.5 increments (e.g., 6.0, 6.5, 7.0, 7.5, 8.0).
- maxScore: 9.0.
- bandLevel: E.g., 'Band 7.5 - Good User (C1)'.
- CRITERIA (Provide 4 official IELTS criteria with maxScore 9.0):
  1. '{$task1Or2}' (Đánh giá trả lời đề bài)
  2. 'Coherence & Cohesion' (Mạch lạc & Liên kết)
  3. 'Lexical Resource' (Vốn từ vựng)
  4. 'Grammatical Range & Accuracy' (Đa dạng & Chính xác ngữ pháp)";
        } else {
            // ielts_academic
            $task1Or2 = ($essayType === 'ielts_academic_task1') ? 'Task Achievement' : 'Task Response';
            $examSpecificRules = "EXAM TYPE: IELTS Academic (Format: {$essayType}).
SCORING RULES & SCALE:
- overallScore: Set IELTS Band Score from 1.0 to 9.0 with 0.5 increments (e.g., 6.0, 6.5, 7.0, 7.5, 8.0).
- maxScore: 9.0.
- bandLevel: E.g., 'Band 7.5 - Good User (C1)'.
- CRITERIA (Provide 4 official IELTS criteria with maxScore 9.0):
  1. '{$task1Or2}' (Đánh giá trả lời đề bài)
  2. 'Coherence & Cohesion' (Mạch lạc & Liên kết)
  3. 'Lexical Resource' (Vốn từ vựng)
  4. 'Grammatical Range & Accuracy' (Đa dạng & Chính xác ngữ pháp)";
        }

        return "You are an expert English writing examiner for international tests (IELTS, TOEIC, TOEFL).

{$examSpecificRules}

TASK INSTRUCTION: Evaluate the student submission based STRICTLY on the prompt/topic and the specific exam criteria above.
Output valid JSON ONLY.
CRITICAL LANGUAGE REQUIREMENT: All comments in criteria, reason in corrections, list items in strengths, and list items in improvements MUST be written in fluent, helpful Vietnamese.

Return a JSON object with this EXACT structure:
{
  \"overallScore\": 7.5,
  \"maxScore\": 9.0,
  \"bandLevel\": \"Band 7.5 - Good User (C1)\",
  \"complexity\": \"B2 - C1 Advanced\",
  \"criteria\": [
    { \"name\": \"Criteria 1\", \"score\": 8.0, \"maxScore\": 9.0, \"comment\": \"Nhận xét bằng tiếng Việt...\" },
    { \"name\": \"Criteria 2\", \"score\": 7.5, \"maxScore\": 9.0, \"comment\": \"Nhận xét bằng tiếng Việt...\" },
    { \"name\": \"Criteria 3\", \"score\": 7.0, \"maxScore\": 9.0, \"comment\": \"Nhận xét bằng tiếng Việt...\" },
    { \"name\": \"Criteria 4\", \"score\": 7.5, \"maxScore\": 9.0, \"comment\": \"Nhận xét bằng tiếng Việt...\" }
  ],
  \"strengths\": [
    \"Điểm mạnh 1 bằng tiếng Việt...\",
    \"Điểm mạnh 2 bằng tiếng Việt...\"
  ],
  \"improvements\": [
    \"Gợi ý cải thiện 1 bằng tiếng Việt...\",
    \"Gợi ý cải thiện 2 bằng tiếng Việt...\"
  ],
  \"corrections\": [
    {
      \"paragraph\": 1,
      \"type\": \"grammar\",
      \"badge\": \"Ngữ pháp\",
      \"original\": \"Original sentence from essay with error\",
      \"suggestion\": \"Corrected or upgraded sentence\",
      \"reason\": \"Giải thích chi tiết lỗi bằng tiếng Việt...\"
    }
  ],
  \"sampleEssay\": \"A polished, high-scoring rewritten model essay in English tailored to this exact prompt and exam format.\"
}";
    }
}



