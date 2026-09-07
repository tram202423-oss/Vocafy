<?php

namespace App\Http\Controllers;

use App\Models\UserVocabulary;
use App\Models\Vocabulary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VocabularyProgressController extends Controller
{
    /**
     * Ghi nhận user vừa xem/ôn một từ vựng (status → learning).
     *
     * POST /vocabulary/{vocabulary}/review
     */
    public function review(Vocabulary $vocabulary)
    {
        $userId = Auth::id();

        $record = UserVocabulary::firstOrNew([
            'user_id'       => $userId,
            'vocabulary_id' => $vocabulary->id,
        ]);

        // Chỉ nâng từ new → learning, không hạ từ mastered
        if ($record->status === 'new' || !$record->exists) {
            $record->status = 'learning';
        }

        $record->review_count   = ($record->review_count ?? 0) + 1;
        $record->last_review_at = now();
        $record->save();

        return response()->json([
            'success' => true,
            'status'  => $record->status,
            'review_count' => $record->review_count,
        ]);
    }

    /**
     * Đánh dấu từ đã thuộc hoàn toàn (status → mastered).
     *
     * POST /vocabulary/{vocabulary}/master
     */
    public function master(Vocabulary $vocabulary)
    {
        $userId = Auth::id();

        $record = UserVocabulary::updateOrCreate(
            ['user_id' => $userId, 'vocabulary_id' => $vocabulary->id],
            [
                'status'        => 'mastered',
                'mastered_at'   => now(),
                'last_review_at'=> now(),
                'review_count'  => \DB::raw('review_count + 1'),
            ]
        );

        return response()->json([
            'success' => true,
            'status'  => 'mastered',
        ]);
    }

    /**
     * Đặt lại trạng thái từ về "new" (bắt đầu học lại).
     *
     * POST /vocabulary/{vocabulary}/reset
     */
    public function reset(Vocabulary $vocabulary)
    {
        $userId = Auth::id();

        UserVocabulary::where('user_id', $userId)
            ->where('vocabulary_id', $vocabulary->id)
            ->update([
                'status'      => 'new',
                'mastered_at' => null,
                'review_count'=> 0,
                'last_review_at' => null,
            ]);

        return response()->json([
            'success' => true,
            'status'  => 'new',
        ]);
    }

    /**
     * Lấy tiến độ học của user theo topic (dùng để load trạng thái ban đầu).
     *
     * GET /progress/topic/{topic}
     */
    public function topicProgress($topicId)
    {
        $userId = Auth::id();

        $progress = UserVocabulary::whereHas('vocabulary', function ($q) use ($topicId) {
                $q->where('topic_id', $topicId);
            })
            ->where('user_id', $userId)
            ->get(['vocabulary_id', 'status', 'review_count']);

        $statusMap = $progress->pluck('status', 'vocabulary_id');

        $total   = $progress->count();
        $learning = $progress->where('status', 'learning')->count();
        $mastered = $progress->where('status', 'mastered')->count();

        return response()->json([
            'success'   => true,
            'statusMap' => $statusMap,
            'summary'   => [
                'total'    => $total,
                'learning' => $learning,
                'mastered' => $mastered,
            ],
        ]);
    }
}
