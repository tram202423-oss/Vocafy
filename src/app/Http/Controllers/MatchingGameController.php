<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Topic;
use App\Models\Vocabulary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MatchingGameController extends Controller
{
    /**
     * Display the Matching Game interface.
     */
    public function index(Request $request)
    {
        // Chỉ lấy categories có ít nhất 1 topic có vocabularies
        $categories = Category::with(['topics' => function ($query) {
            // Chỉ load topics có từ vựng (vocab_count > 0)
            $query->select('id', 'category_id', 'name', 'slug')
                  ->whereHas('vocabularies')
                  ->withCount('vocabularies')
                  ->orderBy('name');
        }])
        ->whereHas('topics', function ($q) {
            // Category phải có ít nhất 1 topic có từ vựng
            $q->whereHas('vocabularies');
        })
        ->orderBy('name')
        ->get(['id', 'name', 'slug']);

        $totalVocabsCount = Vocabulary::count();

        // Determine scope from query parameters
        $scope = $request->query('scope', 'all');
        $topicSlug = $request->query('topic');
        $categorySlug = $request->query('category');

        $selectedTopic = null;
        $selectedCategory = null;

        if ($topicSlug) {
            $selectedTopic = Topic::where('slug', $topicSlug)
                ->orWhere('id', $topicSlug)
                ->first();
            if ($selectedTopic) {
                $scope = 'topic';
                $selectedCategory = Category::find($selectedTopic->category_id);
            }
        } elseif ($categorySlug) {
            $selectedCategory = Category::where('slug', $categorySlug)
                ->orWhere('id', $categorySlug)
                ->first();
            if ($selectedCategory) {
                $scope = 'category';
            }
        }

        // Limit pairs for initial load
        $limit = (int) $request->query('limit', 6);
        $limit = max(4, min(12, $limit));

        $vocabulariesQuery = Vocabulary::query();

        if ($scope === 'topic' && $selectedTopic) {
            $vocabulariesQuery->where('topic_id', $selectedTopic->id);
        } elseif ($scope === 'category' && $selectedCategory) {
            $vocabulariesQuery->whereHas('topic', function ($q) use ($selectedCategory) {
                $q->where('category_id', $selectedCategory->id);
            });
        }

        $initialVocabularies = $vocabulariesQuery
            ->inRandomOrder()
            ->take($limit)
            ->get(['id', 'word', 'meaning', 'pronunciation', 'audio', 'topic_id']);

        return view('user.game.matching', [
            'categories'          => $categories,
            'initialScope'        => $scope,
            'selectedCategoryId'  => $selectedCategory?->id,
            'selectedTopicId'     => $selectedTopic?->id,
            'initialVocabularies' => $initialVocabularies,
            'totalVocabsCount'    => $totalVocabsCount,
        ]);
    }

    /**
     * API to fetch randomized vocabularies based on selected scope.
     */
    public function getVocabularies(Request $request): JsonResponse
    {
        $scope = $request->query('scope', 'all');
        $categoryId = $request->query('category_id');
        $topicId = $request->query('topic_id');
        $limit = (int) $request->query('limit', 6);
        $limit = max(4, min(12, $limit));

        $query = Vocabulary::query();

        if ($scope === 'topic' && !empty($topicId)) {
            $query->where('topic_id', $topicId);
        } elseif ($scope === 'category' && !empty($categoryId)) {
            $query->whereHas('topic', function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            });
        }

        $vocabularies = $query
            ->inRandomOrder()
            ->take($limit)
            ->get(['id', 'word', 'meaning', 'pronunciation', 'audio', 'topic_id']);

        return response()->json([
            'success'      => true,
            'scope'        => $scope,
            'count'        => $vocabularies->count(),
            'vocabularies' => $vocabularies,
        ]);
    }
}
