<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use App\Models\PostCategory;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $categories = Category::with('topics')->orderBy('updated_at', 'desc')->get();
        $postCategories = PostCategory::where('is_active', true)->orderBy('updated_at', 'desc')->get();
        $posts = Post::published()->orderBy('updated_at', 'desc')->get();

        $content = view('sitemap', compact('categories', 'postCategories', 'posts'))->render();

        return response($content, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
        ]);
    }
}
