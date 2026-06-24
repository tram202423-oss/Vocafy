<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Topic;

class TopicController extends Controller
{
    public function index(Category $category, Topic $topic)
    {
        return view('topics.index', compact(
            'category',
            'topic'
        ));
    }
}
