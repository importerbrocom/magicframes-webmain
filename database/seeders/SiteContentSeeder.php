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

        // key => [admin label, small label, title, body]
        $home = [
            'hero' => ['Hero banner', null, null, null],
            'about' => ['About Magic Frames', 'Our Story', 'We turn moments into memories', null],
            'quote' => ['Quote band', null, null, null],
            'services' => ['Services showcase', 'What We Offer', 'Our Services', null],
            'stats' => ['Statistics & achievements', null, null, null],
            'portfolio' => ['Featured photography', 'Captured Moments', 'Our Portfolio', null],
            'videos' => ['Featured films', 'In Motion', 'Featured Films', null],
            'recent' => ['Recent projects', 'Latest Work', 'Recent Projects', null],
            'testimonials' => ['Client testimonials', 'Kind Words', 'Love Notes', null],
            'social' => ['Instagram / social', 'Follow Along', 'On Instagram', null],
            'contact' => ['Contact & enquiry', 'Get in Touch', "Let's plan something beautiful", "Tell us about your day and we'll be in touch within 24 hours."],
        ];
        $order = 0;
        foreach ($home as $key => [$admin, $label, $title, $body]) {
            HomeSection::firstOrCreate(['key' => $key], [
                'admin_label' => $admin, 'label' => $label, 'title' => $title, 'body' => $body,
                'sort_order' => $order++,
                // blocks with no content yet stay hidden until the admin fills them in
                'is_visible' => in_array($key, ['hero', 'about', 'portfolio', 'contact'], true),
            ]);
        }
    }
}
