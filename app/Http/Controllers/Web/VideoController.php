<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Api\V1\VideoController as Api;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\Concerns\BuildsPages;
use App\Services\SiteData;
use Illuminate\Http\Request;

class VideoController extends Controller
{
    use BuildsPages;

    public function index(Request $request)
    {
        $page = $this->sectionPage('videos');
        $categories = collect($this->api(app(Api::class)->categories())['data'])->filter(fn ($c) => $c['videos_count'] > 0)->values()->all();
        $active = $request->query('category');
        abort_if($active && ! collect($categories)->contains('slug', $active), 404);

        $videos = $this->api(app(Api::class)->index($this->query(['category' => $active, 'per_page' => 12])));

        return view('videos', [
            'site' => SiteData::site(), 'page' => $page, 'categories' => $categories, 'active' => $active,
            'videos' => $videos['data'], 'meta' => $videos['meta'],
            'play' => preg_match('/^[a-z0-9-]+$/', (string) $request->query('play')) ? $request->query('play') : null,
            'seo' => $this->seoFrom($page['seo'], url('/videos')), 'bodyClass' => 'page-videos',
        ]);
    }
}
