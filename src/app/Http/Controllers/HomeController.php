<?php

namespace App\Http\Controllers;
use App\Models\Category;
use App\Models\Topic;

class HomeController extends Controller
{
    public function index()
    {
        $categories = Category::withCount('topics')
            ->orderBy('name')
            ->take(4)
            ->get();

        $popularTopics = Topic::withCount('vocabularies')
            ->orderByDesc('vocabularies_count')
            ->take(8)
            ->get();

        return view('home.index', [
            'categories' => $categories,
            'popularTopics' => $popularTopics,
        ]);
    }
}