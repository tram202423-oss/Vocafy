<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Support\Facades\Auth;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount('topics')
            ->orderBy('name')
            ->get();

        return view('user.categories.index', compact('categories'));
    }

    public function show(Category $category)
    {
        $userId = Auth::id();

        $category->load([
            'topics' => function ($query) use ($userId) {
                $counts = ['vocabularies'];

                if ($userId) {
                    $counts['vocabularies as mastered_vocabularies_count'] = function ($q) use ($userId) {
                        $q->whereHas('userVocabularies', function ($uq) use ($userId) {
                            $uq->where('user_id', $userId)->where('status', 'mastered');
                        });
                    };
                    $counts['vocabularies as learning_vocabularies_count'] = function ($q) use ($userId) {
                        $q->whereHas('userVocabularies', function ($uq) use ($userId) {
                            $uq->where('user_id', $userId)->where('status', 'learning');
                        });
                    };
                }

                $query->withCount($counts);
            }
        ]);

        // Thống kê tiến độ tổng của danh mục này (nếu đã đăng nhập)
        $categoryProgress = null;
        if ($userId) {
            $totalVocabs = $category->topics->sum('vocabularies_count');
            $masteredVocabs = $category->topics->sum('mastered_vocabularies_count');
            $learningVocabs = $category->topics->sum('learning_vocabularies_count');
            $percent = $totalVocabs > 0 ? round(($masteredVocabs / $totalVocabs) * 100) : 0;

            $categoryProgress = [
                'total'    => $totalVocabs,
                'mastered' => $masteredVocabs,
                'learning' => $learningVocabs,
                'percent'  => $percent,
            ];
        }

        return view('user.categories.show', compact('category', 'categoryProgress'));
    }
}