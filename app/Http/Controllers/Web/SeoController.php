<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\GalleryCategory;
use App\Models\Page;
use App\Models\Service;
use App\Services\ContentCache;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function sitemap(): Response
    {
        $xml = ContentCache::remember('sitemap', function () {
            $urls = [];
            $add = function (string $path, $lastmod = null, string $freq = 'monthly', string $prio = '0.6') use (&$urls) {
                $urls[] = ['loc' => url($path), 'lastmod' => $lastmod?->toAtomString(), 'freq' => $freq, 'prio' => $prio];
            };

            foreach (Page::published()->with('seo')->get() as $page) {
                if ($page->seo?->noindex) {
                    continue;
                }
                $add($page->path, $page->updated_at, $page->template === 'home' ? 'weekly' : 'monthly', $page->template === 'home' ? '1.0' : '0.8');
            }
            foreach (GalleryCategory::published()->whereHas('publishedImages')->get() as $cat) {
                $add('/gallery/'.$cat->slug, $cat->updated_at, 'weekly', '0.7');
            }

            $w = new \XMLWriter;
            $w->openMemory();
            $w->setIndent(true);
            $w->startDocument('1.0', 'UTF-8');
            $w->startElement('urlset');
            $w->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
            foreach ($urls as $u) {
                $w->startElement('url');
                $w->writeElement('loc', $u['loc']);
                if ($u['lastmod']) {
                    $w->writeElement('lastmod', $u['lastmod']);
                }
                $w->writeElement('changefreq', $u['freq']);
                $w->writeElement('priority', $u['prio']);
                $w->endElement();
            }
            $w->endElement();

            return ['xml' => $w->outputMemory()];
        })['xml'];

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots(): Response
    {
        $body = "User-agent: *\nDisallow: /admin\nDisallow: /api/\nDisallow: /livewire\n\nSitemap: ".url('/sitemap.xml')."\n";

        return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
