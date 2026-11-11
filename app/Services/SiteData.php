<?php

namespace App\Services;

use App\Http\Resources\GalleryCategoryResource;
use App\Http\Resources\GalleryImageResource;
use App\Http\Resources\PageResource;
use App\Http\Resources\ServiceResource;
use App\Http\Resources\TestimonialResource;
use App\Http\Resources\VideoResource;
use App\Models\GalleryCategory;
use App\Models\GalleryImage;
use App\Models\Header;
use App\Models\HeroSlide;
use App\Models\HomeSection;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Statistic;
use App\Models\Testimonial;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Single source of truth for public content. Used by the JSON API *and* the server-rendered
 * pages, so both always show the same (cached, plain-array) data.
 */
class SiteData
{
    public static function site(): array
    {
        return ContentCache::remember('site', fn () => ['data' => self::buildSite()])['data'];
    }

    public static function home(): array
    {
        return ContentCache::remember('home', fn () => ['data' => self::buildHome()])['data'];
    }

    /** @return array<string,mixed>|null null when the page does not exist or is unpublished */
    public static function page(string $slug): ?array
    {
        $cached = ContentCache::remember("pages:show:{$slug}", function () use ($slug) {
            $page = Page::published()->with(['sections', 'seo'])->where('slug', $slug)->first();

            return ['data' => $page ? (new PageResource($page))->resolve() : null];
        });

        return $cached['data'];
    }

    public static function services(): array
    {
        return ContentCache::remember('services:list', fn () => ServiceResource::collection(Service::published()->get())->response()->getData(true))['data'];
    }

    public static function testimonials(): array
    {
        return ContentCache::remember('testimonials:list', fn () => TestimonialResource::collection(Testimonial::published()->get())->response()->getData(true))['data'];
    }

    /** Full data for one service's own page (null when missing / unpublished). */
    public static function service(string $slug): ?array
    {
        $data = ContentCache::remember("services:page:{$slug}", function () use ($slug) {
            $svc = Service::published()->where('slug', $slug)->first();
            if (! $svc) {
                return ['missing' => true];
            }
            $url = fn (?string $path) => $path ? Storage::disk('public')->url($path) : null;

            $photos = collect($svc->gallery ?? [])->filter(fn ($g) => ! empty($g['image']))->map(fn ($g) => [
                'url' => $url($g['image']), 'title' => $g['title'] ?? null, 'alt' => ($g['alt'] ?? null) ?: ($g['title'] ?? null),
            ])->values()->all();

            $videos = collect($svc->videos ?? [])->map(function ($v) use ($url) {
                $title = $v['title'] ?? 'Video';
                if (($v['source'] ?? 'link') === 'upload') {
                    if (empty($v['file'])) {
                        return null;
                    }

                    return ['title' => $title, 'description' => $v['description'] ?? null, 'embed' => null, 'file' => $url($v['file']), 'thumb' => $url($v['thumbnail'] ?? null)];
                }
                $parsed = VideoLinkParser::parse($v['url'] ?? null);
                if (! $parsed) {
                    return null;
                }

                return [
                    'title' => $title, 'description' => $v['description'] ?? null,
                    'embed' => VideoLinkParser::embedUrl($parsed['type'], $parsed['id']), 'file' => null,
                    'thumb' => $url($v['thumbnail'] ?? null) ?: VideoLinkParser::thumbnailUrl($parsed['type'], $parsed['id']),
                ];
            })->filter()->values()->all();

            return [
                'id' => $svc->id, 'title' => $svc->title, 'slug' => $svc->slug, 'icon' => $svc->icon,
                'short_description' => $svc->short_description, 'description' => $svc->description,
                'image_url' => $svc->image_url,
                'highlights' => collect($svc->highlights ?? [])->map(fn ($h) => is_array($h) ? ($h['text'] ?? null) : $h)->filter()->values()->all(),
                'faqs' => collect($svc->faqs ?? [])->filter(fn ($f) => filled($f['question'] ?? null) && filled($f['answer'] ?? null))->values()->all(),
                'photos' => $photos, 'videos' => $videos,
                'seo' => ['title' => $svc->seo_title, 'description' => $svc->seo_description],
            ];
        }, 86400);

        return empty($data['missing']) ? $data : null;
    }

    private static function buildSite(): array
    {
        $header = Header::current();
        $menus = NavigationMenu::visible()->with('page')->get()->groupBy('location');
        $items = fn (string $loc) => ($menus[$loc] ?? collect())
            ->filter(fn ($m) => ! $m->page_id || ($m->page && $m->page->is_published))
            ->map(fn ($m) => ['label' => $m->label, 'url' => $m->href, 'new_tab' => $m->open_in_new_tab])->values()->all();

        $social = collect(['instagram', 'facebook', 'youtube', 'pinterest'])
            ->mapWithKeys(fn ($k) => [$k => Setting::get("social.$k")])->filter()->all();
        $ogDefault = Setting::get('seo.default_og_image');

        return [
            'name' => Setting::get('site.name', 'Magic Frames'),
            'tagline' => Setting::get('site.tagline'),
            'effects' => Setting::get('site.effects', '1') !== '0',
            'header' => [
                'visible' => $header->is_visible,
                'sticky' => $header->is_sticky,
                'background_style' => $header->background_style,
                'logo_url' => $header->logo_url,
                'logo_alt' => $header->logo_alt,
                'cta' => $header->show_cta ? ['label' => $header->cta_label, 'url' => $header->cta_url] : null,
                'floating_button' => $header->show_floating_button,
                'social_in_mobile_menu' => $header->show_social_in_mobile_menu,
            ],
            'menus' => [
                'left' => $items('header_left'),
                'right' => $items('header_right'),
                'mobile' => array_merge($items('header_left'), $items('header_right'), $items('mobile_only')),
                'footer' => $items('footer'),
            ],
            'footer' => [
                'about' => Setting::get('footer.about'),
                'copyright' => Setting::get('footer.copyright'),
            ],
            'contact' => [
                'phone' => Setting::get('contact.phone'),
                'whatsapp' => Setting::get('contact.whatsapp'),
                'email' => Setting::get('contact.email'),
                'address' => Setting::get('contact.address'),
                'hours' => Setting::get('contact.hours'),
                'map_embed_url' => Setting::get('contact.map_embed_url'),
            ],
            'social' => $social,
            'seo' => [
                'default_description' => Setting::get('seo.default_description'),
                'default_og_image' => $ogDefault ? Storage::disk('public')->url($ogDefault) : null,
            ],
        ];
    }

    /**
     * Home "Portfolio" block: the photos marked Featured; when none are featured, the newest published
     * photos, so the block never disappears just because nothing is featured. With no photos at all it falls back
     * to the gallery categories' cover images.
     */
    private static function portfolioItems(int $limit, Request $request): array
    {
        $featured = GalleryImage::published()->with('category')->where('is_featured', true)
            ->orderBy('sort_order')->orderByDesc('id')->limit($limit)->get();
        if ($featured->isEmpty()) {   // nothing marked Featured: show the newest photos instead
            $featured = GalleryImage::published()->with('category')->latest('id')->limit($limit)->get();
        }
        if ($featured->isNotEmpty()) {
            return GalleryImageResource::collection($featured)->resolve($request);
        }

        return GalleryCategory::published()->whereNotNull('cover_path')->orderBy('sort_order')->orderBy('name')->limit($limit)->get()
            ->map(fn (GalleryCategory $c) => [
                'title' => $c->name, 'alt' => $c->name, 'thumb_url' => img_url($c->cover_url, 1400), 'srcset' => null,
                'category' => ['slug' => $c->slug, 'name' => $c->name],
            ])->all();
    }

    private static function buildHome(): array
    {
        $request = Request::create('/');
        $sections = HomeSection::visible()->get()->map(function (HomeSection $s) use ($request) {
            $limit = max(1, $s->items_limit ?: 6);
            $items = match ($s->key) {
                'services' => ServiceResource::collection(Service::published()->limit($limit)->get())->resolve($request),
                'stats' => Statistic::published()->limit($limit)->get()->map(fn ($x) => [
                    'label' => $x->label, 'value' => $x->value, 'suffix' => $x->suffix,
                ])->all(),
                'portfolio' => self::portfolioItems($limit, $request),
                'videos' => VideoResource::collection(
                    Video::published()->with('category')->where('is_featured', true)->orderBy('sort_order')->orderByDesc('id')->limit($limit)->get()
                )->resolve($request),
                'recent' => GalleryCategoryResource::collection(
                    GalleryCategory::published()->withCount(['publishedImages as published_images_count'])
                        ->whereHas('publishedImages')->latest('updated_at')->limit($limit)->get()
                )->resolve($request),
                'testimonials' => TestimonialResource::collection(Testimonial::published()->limit($limit)->get())->resolve($request),
                default => null,
            };

            // List-type blocks with nothing to show are dropped so no empty section renders.
            if (in_array($s->key, ['services', 'stats', 'portfolio', 'videos', 'recent', 'testimonials'], true) && empty($items)) {
                return null;
            }

            return [
                'key' => $s->key,
                'label' => $s->label,
                'title' => $s->title,
                'body' => $s->body,
                'body_secondary' => $s->body_secondary,
                'image_url' => $s->image_url,
                'image_secondary_url' => $s->image_secondary_url,
                'button_label' => $s->button_label,
                'button_url' => $s->button_url,
                'items' => $items,
            ];
        })->filter()->values()->all();

        return [
            // names of galleries that have photos: scrolling band under the hero
            'marquee' => GalleryCategory::published()->whereHas('publishedImages')->orderBy('sort_order')->orderBy('name')->pluck('name')->all(),
            'hero' => HeroSlide::published()->get()->map(fn ($h) => [
                'heading' => $h->heading, 'subheading' => $h->subheading, 'description' => $h->description,
                'image_url' => $h->image_url, 'video_url' => $h->video_url, 'video_embed' => $h->video_embed, 'button_label' => $h->button_label, 'button_url' => $h->button_url,
            ])->all(),
            'sections' => $sections,
        ];
    }
}
