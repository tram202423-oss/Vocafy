<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $categories = Category::with('topics')->orderBy('updated_at', 'desc')->get();

        $content = view('sitemap', compact('categories'))->render();

        return response($content, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
        ]);
    }
}
