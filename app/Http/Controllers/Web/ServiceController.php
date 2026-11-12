<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\SiteData;
use Illuminate\Contracts\View\View;

class ServiceController extends Controller
{
    public function show(string $slug): View
    {
        $service = SiteData::service($slug);
        abort_if($service === null, 404);

        $site = SiteData::site();
        $others = collect(SiteData::services())->where('slug', '!=', $slug)->values()->all();

        return view('service-show', [
            'site' => $site, 'service' => $service, 'others' => $others,
            'seo' => [
                'title' => ($service['seo']['title'] ?: $service['title']).' | '.$site['name'],
                'description' => $service['seo']['description'] ?: $service['short_description'],
                'og_image' => $service['image_url'] ?: ($service['photos'][0]['url'] ?? null),
                'canonical' => url('/services/'.$service['slug']),
                'noindex' => false,
            ],
            'bodyClass' => 'page-service-show',
        ]);
    }
}
