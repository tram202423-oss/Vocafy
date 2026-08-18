<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Topic;

class TopicController extends Controller
{
    public function index(Category $category, Topic $topic)
    {
        $topic->load('vocabularies');
        return view('user.topics.index', compact(
            'category',
            'topic'
        ));
    }
}
