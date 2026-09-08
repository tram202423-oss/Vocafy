<?php

namespace App\Http\Controllers;

use App\Services\GeminiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WritingAiController extends Controller
{
    public function __construct(private readonly GeminiService $gemini) {}

    public function index()
    {
        return view('user.writing-ai.index');
    }

    public function evaluate(Request $request): JsonResponse
    {
        $request->validate([
            'topic'             => 'nullable|string',
            'essay'             => 'required|string|min:15',
            'exam_category'     => 'nullable|string',
            'essay_type'        => 'nullable|string',
            'image'             => 'nullable|array',
            'image.data'        => 'nullable|string',
            'image.mime_type'   => 'nullable|string',
        ]);

        $topic      = $request->input('topic', '');
        $essay      = $request->input('essay');
        $imageInput = $request->input('image');
        $hasImage   = ! empty($imageInput['data']) && ! empty($imageInput['mime_type']);

        if (empty($topic) && ! $hasImage) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng nhập đề bài hoặc tải lên ảnh biểu đồ / sơ đồ đề bài!',
            ], 422);
        }

        try {
            $result = $this->gemini->evaluateWriting(
                topic:        $topic,
                essay:        $essay,
                examCategory: $request->input('exam_category', 'ielts_academic'),
                essayType:    $request->input('essay_type', 'ielts_academic_task2'),
                hasImage:     $hasImage,
                imageInput:   $imageInput,
            );

            return response()->json([
                'success' => true,
                'data'    => $result,
            ]);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Gemini API Connection Timeout: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Kết nối với Gemini API bị quá thời gian (Timeout). Vui lòng kiểm tra lại mạng hoặc thử nộp bài lại!',
            ], 504);

        } catch (\Exception $e) {
            Log::error('Gemini AI Evaluation Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Lỗi kết nối Gemini API: ' . $e->getMessage(),
            ], 500);
        }
    }
}
