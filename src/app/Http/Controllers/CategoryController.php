<?php

namespace App\Http\Controllers;

use App\Models\Category;

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
        $category->load([
            'topics' => function ($query) {
                $query->withCount('vocabularies');
            }
        ]);

        return view('user.categories.show', compact('category'));
    }
}