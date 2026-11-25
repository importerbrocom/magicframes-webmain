<?php

namespace Database\Seeders;

use App\Models\Header;
use App\Models\HomeSection;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Seeds the *structure* of the site: header, navigation (mirrors the original static site),
 * system pages, home page blocks, and the few generic copy lines that already existed.
 *
 * Intentionally NOT seeded (must come from the studio via the admin): phone, email, address,
 * opening hours, social links, services, testimonials, statistics, legal text, award claims.
 * Idempotent: safe to run on every deploy, never overwrites admin edits.
 */
class SiteContentSeeder extends Seeder
{
    public function run(): void
    {
        Header::current();

        foreach (['site.name' => 'Magic Frames', 'footer.about' => 'Boutique wedding & event studio crafting timeless celebrations with heart.', 'footer.copyright' => 'Magic Frames. Crafted with love.'] as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }

        // [slug, title, template, published, banner_label, banner_title, meta description]
        $pages = [
            ['home', 'Home', 'home', true, null, null, null],
            ['about', 'About Us', 'about', true, 'Who We Are', 'Our Story', 'The Magic Frames story — a boutique wedding and event studio.'],
            ['services', 'Services', 'services', true, 'What We Offer', 'Our Services', null],
            ['portfolio', 'Portfolio', 'portfolio', true, 'Our Work', 'Portfolio', null],
            ['gallery', 'Photo Gallery', 'gallery', true, 'Captured Moments', 'Gallery', null],
            ['videos', 'Video Gallery', 'videos', true, 'In Motion', 'Films', null],
            ['blog', 'Blog', 'blog', true, 'Stories & Ideas', 'Journal', null],
            ['contact', 'Contact Us', 'contact', true, 'Say Hello', 'Contact', 'Get in touch with Magic Frames to plan your celebration.'],
            ['shop', 'Shop', 'shop', true, 'Shop', 'Shop', null],
            // Legal pages stay unpublished until the studio supplies real text.
            ['privacy-policy', 'Privacy Policy', 'default', false, 'Legal', 'Privacy Policy', null],
            ['terms-and-conditions', 'Terms and Conditions', 'default', false, 'Legal', 'Terms and Conditions', null],
        ];
        foreach ($pages as [$slug, $title, $template, $published, $label, $bannerTitle, $meta]) {
            $page = Page::firstOrCreate(['slug' => $slug], [
                'title' => $title, 'template' => $template, 'is_system' => true, 'is_published' => $published,
                'banner_label' => $label, 'banner_title' => $bannerTitle,
            ]);
            if ($page->wasRecentlyCreated && $meta) {
                $page->seo()->create(['meta_description' => $meta]);
            }
        }

        if (NavigationMenu::count() === 0) {
            $nav = [
                'header_left' => [['Home', '/'], ['About', '/about'], ['Portfolio', '/portfolio']],
                'header_right' => [['Blog', '/blog'], ['Shop', '/shop'], ['Contact', '/contact']],
                'mobile_only' => [['Services', '/services']],
                'footer' => [['Our Story', '/about'], ['Services', '/services'], ['Portfolio', '/portfolio'], ['Blog', '/blog'], ['Videos', '/videos']],
            ];
            foreach ($nav as $location => $items) {
                foreach ($items as $i => [$label, $url]) {
                    NavigationMenu::create(['location' => $location, 'label' => $label, 'url' => $url, 'sort_order' => $i]);
                }
            }
        }

        // Footer links that must exist on every install (idempotent; page-linked items vanish by themselves while their page is unpublished)
        foreach ([
            ['Gallery', null, '/gallery', 5],
            ['Privacy Policy', 'privacy-policy', null, 20],
            ['Terms & Conditions', 'terms-and-conditions', null, 21],
        ] as [$label, $pageSlug, $url, $order]) {
            $page = $pageSlug ? Page::where('slug', $pageSlug)->first() : null;
            NavigationMenu::firstOrCreate(
                ['location' => 'footer', 'label' => $label],
                ['page_id' => $page?->id, 'url' => $url, 'sort_order' => $order],
            );
        }

        $order = 0;
        foreach (HomeSection::DEFAULTS as $key => [$admin, $label, $title, $body, $visible]) {
            HomeSection::firstOrCreate(['key' => $key], [
                'admin_label' => $admin, 'label' => $label, 'title' => $title, 'body' => $body,
                'sort_order' => $order++,
                'is_visible' => $visible,   // blocks with no content yet stay hidden until the admin fills them in
            ]);
        }
    }
}
