<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Topic;
use App\Models\UserVocabulary;
use Illuminate\Support\Facades\Auth;

class TopicController extends Controller
{
    public function index(Category $category, Topic $topic)
    {
        $topic->load('vocabularies');

        // Lấy tiến độ học của user hiện tại (nếu đã đăng nhập)
        $progressMap = [];
        $progressSummary = ['total' => 0, 'learning' => 0, 'mastered' => 0];

        if (Auth::check()) {
            $userProgress = UserVocabulary::where('user_id', Auth::id())
                ->whereIn('vocabulary_id', $topic->vocabularies->pluck('id'))
                ->get(['vocabulary_id', 'status']);

            $progressMap = $userProgress->pluck('status', 'vocabulary_id')->toArray();

            $progressSummary = [
                'total'    => $topic->vocabularies->count(),
                'learning' => $userProgress->where('status', 'learning')->count(),
                'mastered' => $userProgress->where('status', 'mastered')->count(),
            ];
        }

        return view('user.topics.index', compact(
            'category',
            'topic',
            'progressMap',
            'progressSummary'
        ));
    }
}

