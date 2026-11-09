<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Api\V1\GalleryController as Api;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\Concerns\BuildsPages;
use App\Services\SiteData;
use Illuminate\Http\Request;

class GalleryController extends Controller
{
    use BuildsPages;

    private const PER_PAGE = 24;

    private function categories(): array
    {
        return collect($this->api(app(Api::class)->categories())['data'])->filter(fn ($c) => $c['images_count'] > 0)->values()->all();
    }

    /** /portfolio — one card per gallery category. */
    public function portfolio()
    {
        $page = $this->sectionPage('portfolio');

        return view('portfolio', [
            'site' => SiteData::site(), 'page' => $page, 'categories' => $this->categories(),
            'seo' => $this->seoFrom($page['seo']), 'bodyClass' => 'page-portfolio',
        ]);
    }

    /** /gallery[?category=slug] — all photos with category filter, masonry/grid, lightbox. */
    public function index(Request $request)
    {
        $page = $this->sectionPage('gallery');
        $categories = $this->categories();
        $active = $request->query('category');
        abort_if($active && ! collect($categories)->contains('slug', $active), 404);

        $images = $this->api(app(Api::class)->images($this->query(['category' => $active, 'per_page' => self::PER_PAGE])));

        return view('gallery', [
            'site' => SiteData::site(), 'page' => $page, 'categories' => $categories, 'active' => $active,
            'images' => $images['data'], 'meta' => $images['meta'],
            'seo' => $this->seoFrom($page['seo'], url('/gallery')), 'bodyClass' => 'page-gallery',
        ]);
    }

    /** /gallery/{category} — individual gallery. */
    public function show(Request $request, string $slug)
    {
        $payload = $this->api(app(Api::class)->category($this->query(['per_page' => self::PER_PAGE]), $slug));
        $category = $payload['category'];
        abort_if($category['images_count'] < 1, 404);

        $all = $this->categories();
        $i = collect($all)->search(fn ($c) => $c['slug'] === $slug);
        $prev = $i !== false && $i > 0 ? $all[$i - 1] : null;
        $next = $i !== false && $i < count($all) - 1 ? $all[$i + 1] : null;

        return view('gallery-show', [
            'site' => SiteData::site(), 'category' => $category,
            'images' => $payload['images']['data'], 'meta' => $payload['images']['meta'],
            'prev' => $prev, 'next' => $next,
            'seo' => [
                'title' => $category['meta_title'].' — '.SiteData::site()['name'],
                'description' => $category['meta_description'],
                'og_image' => $category['cover_url'],
                'canonical' => url('/gallery/'.$slug),
            ],
            'bodyClass' => 'page-gallery-show',
        ]);
    }
}
