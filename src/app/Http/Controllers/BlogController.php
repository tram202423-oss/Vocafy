<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BlogController extends Controller
{
    /**
     * Display a listing of blog posts with search and category filtering.
     */
    public function index(Request $request)
    {
        $search = $request->query('q');

        $categories = PostCategory::where('is_active', true)
            ->withCount('publishedPosts')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $postsQuery = Post::published()
            ->with(['category', 'author'])
            ->latest('published_at');

        if ($search) {
            $postsQuery->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('excerpt', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        // Bài viết tiêu điểm (chỉ lấy nếu không có query search & ở trang 1)
        $featuredPost = null;
        if (!$search && (!$request->has('page') || $request->query('page') == 1)) {
            $featuredPost = (clone $postsQuery)->first();
        }

        if ($featuredPost) {
            $postsQuery->where('id', '!=', $featuredPost->id);
        }

        $posts = $postsQuery->paginate(9)->withQueryString();

        return view('user.blog.index', compact('categories', 'posts', 'featuredPost', 'search'));
    }

    /**
     * Display posts by specific category.
     */
    public function category(PostCategory $category, Request $request)
    {
        $search = $request->query('q');

        $categories = PostCategory::where('is_active', true)
            ->withCount('publishedPosts')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $postsQuery = $category->publishedPosts()
            ->with(['category', 'author'])
            ->latest('published_at');

        if ($search) {
            $postsQuery->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('excerpt', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        $posts = $postsQuery->paginate(9)->withQueryString();

        return view('user.blog.category', compact('category', 'categories', 'posts', 'search'));
    }

    /**
     * Display a single blog post.
     */
    public function show(Post $post)
    {
        // Kiểm tra quyền: nếu chưa công khai, chỉ admin/editor mới được xem trước
        if ($post->status !== 'published' || ($post->published_at && $post->published_at->isFuture())) {
            /** @var \App\Models\User|null $user */
            $user = Auth::user();
            if (!$user || !$user->hasAnyRole(['super-admin', 'admin', 'editor', 'moderator'])) {
                abort(404);
            }
        }

        // Tăng lượt xem (sử dụng session để tránh spam F5)
        $viewedKey = 'viewed_post_' . $post->id;
        if (!session()->has($viewedKey)) {
            $post->increment('views_count');
            session()->put($viewedKey, true);
        }

        $post->load(['category', 'author']);

        // Bài viết liên quan cùng chuyên mục
        $relatedPosts = Post::published()
            ->where('post_category_id', $post->post_category_id)
            ->where('id', '!=', $post->id)
            ->with(['category', 'author'])
            ->latest('published_at')
            ->take(3)
            ->get();

        // Chuyên mục phổ biến
        $categories = PostCategory::where('is_active', true)
            ->withCount('publishedPosts')
            ->orderBy('sort_order')
            ->get();

        // Bài viết được xem nhiều nhất
        $popularPosts = Post::published()
            ->where('id', '!=', $post->id)
            ->orderByDesc('views_count')
            ->take(4)
            ->get();

        return view('user.blog.show', compact('post', 'relatedPosts', 'categories', 'popularPosts'));
    }
}
