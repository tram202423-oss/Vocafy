<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
            http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">

    {{-- Trang chủ --}}
    <url>
        <loc>{{ route('home') }}</loc>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>

    {{-- Trang danh mục tổng hợp --}}
    <url>
        <loc>{{ route('categories.index') }}</loc>
        <changefreq>weekly</changefreq>
        <priority>0.95</priority>
    </url>

    <url>
        <loc>{{ route('game.matching') }}</loc>
        <changefreq>weekly</changefreq>
        <priority>0.9</priority>
    </url>

    <url>
        <loc>{{ route('writingAi') }}</loc>
        <changefreq>weekly</changefreq>
        <priority>0.85</priority>
    </url>

    {{-- Từng danh mục --}}
    @foreach($categories as $category)
    <url>
        <loc>{{ route('categories.show', $category->slug) }}</loc>
        <lastmod>{{ $category->updated_at->toAtomString() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>

    {{-- Từng chủ đề trong danh mục --}}
    @foreach($category->topics as $topic)
    <url>
        <loc>{{ route('topics.index', ['category' => $category->slug, 'topic' => $topic->slug]) }}</loc>
        <lastmod>{{ $topic->updated_at->toAtomString() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.75</priority>
    </url>
    @endforeach

    @endforeach

</urlset>
