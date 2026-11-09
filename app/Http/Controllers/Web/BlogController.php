<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Api\V1\BlogController as Api;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\Concerns\BuildsPages;
use App\Services\SiteData;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    use BuildsPages;

    public function index(Request $request)
    {
        $page = $this->sectionPage('blog');
        $category = $request->query('category');
        $search = trim((string) $request->query('q', ''));
        $current = max(1, (int) $request->query('page', 1));

        $posts = $this->api(app(Api::class)->index($this->query([
            'category' => $category, 'search' => $search !== '' ? mb_substr($search, 0, 100) : null, 'page' => $current,
        ])));

        return view('blog', [
            'site' => SiteData::site(), 'page' => $page, 'posts' => $posts['data'], 'meta' => $posts['meta'],
            'categories' => $this->api(app(Api::class)->categories())['data'], 'activeCategory' => $category, 'search' => $search,
            'seo' => $this->seoFrom($page['seo'], url('/blog')), 'bodyClass' => 'page-blog',
        ]);
    }

    public function show(Request $request, string $slug)
    {
        $post = $this->api(app(Api::class)->show($request, $slug))['data'];
        $site = SiteData::site();

        return view('blog-show', [
            'site' => $site, 'post' => $post,
            'seo' => $this->seoFrom($post['seo'], url('/blog/'.$slug)),
            'bodyClass' => 'page-blog-show',
        ]);
    }
}
