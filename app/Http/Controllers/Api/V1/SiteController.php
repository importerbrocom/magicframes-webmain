<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Header;
use App\Models\NavigationMenu;
use App\Models\Setting;
use App\Services\ContentCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class SiteController extends Controller
{
    /** Global data every page needs: branding, header, menus, footer, contact & social links. */
    public function show(): JsonResponse
    {
        return response()->json(ContentCache::remember('site', fn () => ['data' => self::payload()]));
    }

    public static function payload(): array
    {
        $header = Header::current();
        $menus = NavigationMenu::visible()->with('page')->get()->groupBy('location');
        $items = fn (string $loc) => ($menus[$loc] ?? collect())
            ->filter(fn ($m) => ! $m->page_id || ($m->page && $m->page->is_published))
            ->map(fn ($m) => ['label' => $m->label, 'url' => $m->href, 'new_tab' => $m->open_in_new_tab])->values();

        $socialKeys = ['instagram', 'facebook', 'youtube', 'pinterest'];
        $ogDefault = Setting::get('seo.default_og_image');

        return [
            'name' => Setting::get('site.name', 'Magic Frames'),
            'tagline' => Setting::get('site.tagline'),
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
                'mobile' => $items('header_left')->concat($items('header_right'))->concat($items('mobile_only'))->values(),
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
            'social' => collect($socialKeys)->mapWithKeys(fn ($k) => [$k => Setting::get("social.$k")])->filter()->all(),
            'seo' => [
                'default_description' => Setting::get('seo.default_description'),
                'default_og_image' => $ogDefault ? Storage::disk('public')->url($ogDefault) : null,
            ],
        ];
    }
}
