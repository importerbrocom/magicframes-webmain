<?php

namespace App\Http\Controllers\Web\Concerns;

use App\Services\SiteData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait BuildsPages
{
    /** Published admin page for a built-in section (gallery, videos, blog…) or 404. */
    protected function sectionPage(string $slug): array
    {
        $page = SiteData::page($slug);
        abort_if($page === null, 404);

        return $page;
    }

    /** @return array<string,mixed> */
    protected function seoFrom(array $seo, ?string $canonical = null): array
    {
        return [
            'title' => $seo['title'] ?? null,
            'description' => $seo['description'] ?? null,
            'og_image' => $seo['og_image'] ?? null,
            'canonical' => $seo['canonical'] ?? $canonical,
            'noindex' => $seo['noindex'] ?? false,
        ];
    }

    /** Call one of our own API controller actions and get its decoded payload (cached, identical to the public API). */
    protected function api(JsonResponse $response): array
    {
        return $response->getData(true);
    }

    protected function query(array $params): Request
    {
        return Request::create('/', 'GET', array_filter($params, fn ($v) => $v !== null && $v !== ''));
    }
}
