<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\SiteData;
use Illuminate\Contracts\View\View;

class PageController extends Controller
{
    public function home(): View
    {
        $site = SiteData::site();
        $home = SiteData::home();
        $page = SiteData::page('home');

        return view('home', [
            'site' => $site,
            'home' => $home,
            'page' => $page,
            'seo' => $this->seo($page, $site['name'], canonical: url('/')),
            'bodyClass' => 'page-home',
        ]);
    }

    public function show(string $slug): View
    {
        $page = SiteData::page($slug);
        abort_if($page === null || $page['template'] === 'home', 404);

        $site = SiteData::site();
        $data = [
            'site' => $site,
            'page' => $page,
            'seo' => $this->seo($page, $site['name']),
            'bodyClass' => 'page-'.$page['template'],
        ];

        return match ($page['template']) {
            'services' => view('services', $data + ['services' => SiteData::services()]),
            'contact' => view('contact', $data),
            default => view('page', $data),
        };
    }

    /** @param array<string,mixed>|null $page */
    private function seo(?array $page, string $fallbackTitle, ?string $canonical = null): array
    {
        $seo = $page['seo'] ?? [];

        return [
            'title' => $seo['title'] ?? $fallbackTitle,
            'description' => $seo['description'] ?? null,
            'og_image' => $seo['og_image'] ?? null,
            'canonical' => $seo['canonical'] ?? $canonical,
            'noindex' => $seo['noindex'] ?? false,
        ];
    }
}
