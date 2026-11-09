<?php

namespace Tests\Feature;

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
use Database\Seeders\RolesAndAdminSeeder;
use Database\Seeders\SiteContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FrontendTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->seed([RolesAndAdminSeeder::class, SiteContentSeeder::class]);
    }

    public function test_home_renders_with_no_leftover_template_content(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        foreach (['Florence', 'Rosewood', 'Lorem ipsum', 'Joseph Harris', 'Est. 2010', 'unsplash.com', 'countdown', 'Happy Couples'] as $bad) {
            $this->assertStringNotContainsString($bad, $html, "template placeholder '$bad' must not appear");
        }
        $this->assertStringContainsString('<title>', $html);
        $this->assertStringContainsString('css/style.css', $html);
        $this->assertStringContainsString('css/enhancements.css', $html);
        $this->assertStringContainsString('Cormorant+Garamond', $html, 'original fonts are kept');
        // header from the database, same structure as the original
        foreach (['Home', 'About', 'Portfolio', 'Blog', 'Shop', 'Contact', 'RSVP'] as $label) {
            $this->assertStringContainsString($label, $html);
        }
        $this->assertStringContainsString('class="hero"', $html);
        $this->assertStringContainsString('Skip to content', $html);
    }

    public function test_home_shows_admin_content_hero_services_stats_testimonials_and_featured_work(): void
    {
        HeroSlide::create(['heading' => 'Timeless Frames', 'subheading' => 'Weddings & Films', 'button_label' => 'Enquire', 'button_url' => '/contact',
            'image_path' => UploadedFile::fake()->image('h.jpg', 2400, 1200)->store('hero', 'public')]);
        Service::create(['title' => 'Wedding Photography', 'short_description' => 'Full-day coverage', 'icon' => 'fa-solid fa-camera']);
        Statistic::create(['label' => 'Weddings', 'value' => 120, 'suffix' => '+']);
        Testimonial::create(['client_name' => 'Real Client', 'client_role' => 'Bride', 'rating' => 5, 'body' => 'Wonderful experience.']);
        $cat = GalleryCategory::create(['name' => 'Weddings']);
        GalleryImage::create(['gallery_category_id' => $cat->id, 'title' => 'Vows', 'is_featured' => true,
            'path' => UploadedFile::fake()->image('v.jpg', 1400, 900)->store('gallery', 'public')]);
        Video::create(['title' => 'Highlights Film', 'source_type' => 'youtube', 'video_url' => 'https://youtu.be/dQw4w9WgXcQ', 'is_featured' => true]);
        HomeSection::whereIn('key', ['services', 'stats', 'testimonials', 'videos', 'recent'])->get()->each->update(['is_visible' => true]);

        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('Timeless Frames', $html);
        $this->assertStringContainsString('<h1 class="hero__word hero__word--long">Timeless Frames</h1>', $html);
        $this->assertStringContainsString('/storage/_cache/1900/', $html, 'hero image is served as a resized WebP');
        $this->assertStringContainsString('Wedding Photography', $html);
        $this->assertStringContainsString('fa-solid fa-camera', $html);
        $this->assertStringContainsString('data-count="120"', $html);
        $this->assertStringContainsString('data-suffix="+"', $html);
        $this->assertStringContainsString('Real Client', $html);
        $this->assertStringContainsString('Wonderful experience.', $html);
        $this->assertStringContainsString('href="/gallery/weddings"', $html);
        $this->assertStringContainsString('srcset=', $html);
        $this->assertStringContainsString('Highlights Film', $html);
        $this->assertStringContainsString('/videos?play=highlights-film', $html);
        Storage::disk('public')->assertExists(collect(Storage::disk('public')->allFiles('_cache/1900'))->first());
    }

    public function test_home_falls_back_gracefully_without_hero_slides(): void
    {
        $this->get('/')->assertOk()->assertSee('hero__slide--plain', false)->assertSee('<h1 class="hero__word">Magic Frames</h1>', false);
    }

    public function test_hidden_home_sections_and_order_are_respected(): void
    {
        HomeSection::where('key', 'about')->first()->update(['title' => 'Our Real Story', 'body' => "First paragraph.\n\nSecond paragraph.", 'is_visible' => true, 'sort_order' => 50]);
        HomeSection::where('key', 'contact')->first()->update(['sort_order' => 40]);
        $html = $this->get('/')->getContent();
        $this->assertStringContainsString('Our Real Story', $html);
        $this->assertStringContainsString('<p>First paragraph.</p>', $html);
        $this->assertLessThan(strpos($html, 'id="about"'), strpos($html, 'id="contact"'), 'contact should now come before about');

        HomeSection::where('key', 'about')->first()->update(['is_visible' => false]);
        $this->get('/')->assertDontSee('Our Real Story');
    }

    public function test_footer_and_contact_show_only_real_details(): void
    {
        $empty = $this->get('/contact')->assertOk()->getContent();
        $this->assertStringNotContainsString('contact-info__item', $empty);
        $this->assertStringNotContainsString('<iframe title', $empty);
        $this->assertStringNotContainsString('<h4>Visit</h4>', $empty);
        $this->assertStringNotContainsString('<h4>Follow</h4>', $empty);

        Setting::setMany([
            'contact.phone' => '+91 90000 11111', 'contact.email' => 'hello@example.test', 'contact.address' => "1 Example Road\nSample City",
            'contact.hours' => 'Mon-Sat 10-6', 'contact.whatsapp' => '+91 90000 22222',
            'contact.map_embed_url' => 'https://www.google.com/maps/embed?pb=example',
            'social.instagram' => 'https://instagram.com/example', 'social.facebook' => 'https://facebook.com/example',
        ]);
        $html = $this->get('/contact')->assertOk()->getContent();
        foreach (['+91 90000 11111', 'tel:+919000011111', 'hello@example.test', 'mailto:hello@example.test', '1 Example Road', 'Mon-Sat 10-6',
            'https://wa.me/919000022222', 'google.com/maps/embed?pb=example', 'https://instagram.com/example', '<h4>Visit</h4>', '<h4>Follow</h4>'] as $needle) {
            $this->assertStringContainsString($needle, $html);
        }
        // social icons also in the mobile menu
        $this->assertStringContainsString('mobile-menu__social', $html);
    }

    public function test_contact_page_has_working_form_markup(): void
    {
        $html = $this->get('/contact')->assertOk()->getContent();
        $this->assertStringContainsString('data-enquiry', $html);
        foreach (['name="name"', 'name="email"', 'name="phone"', 'name="event_type"', 'name="event_date"', 'name="message"', 'name="website"'] as $f) {
            $this->assertStringContainsString($f, $html);
        }
        $this->assertStringContainsString('<option value="Wedding">Wedding</option>', $html);
        $this->assertStringContainsString('class="hp"', $html, 'honeypot present');
        $this->assertStringContainsString('js/site.js', $html);
    }

    public function test_seo_tags_come_from_the_page_and_defaults(): void
    {
        Setting::setMany(['seo.default_description' => 'Default site description']);
        $about = Page::where('slug', 'about')->first();
        $about->seo->update(['meta_title' => 'About the Studio', 'meta_description' => 'Who we are.']);

        $html = $this->get('/about')->assertOk()->getContent();
        $this->assertStringContainsString('<title>About the Studio — Magic Frames</title>', $html);
        $this->assertStringContainsString('<meta name="description" content="Who we are." />', $html);
        $this->assertStringContainsString('<link rel="canonical" href="'.url('/about').'" />', $html);
        $this->assertStringContainsString('<meta property="og:title" content="About the Studio — Magic Frames" />', $html);
        $this->assertStringContainsString('twitter:card', $html);
        $this->assertStringNotContainsString('noindex', $html);

        $about->seo->update(['noindex' => true]);
        $this->get('/about')->assertSee('<meta name="robots" content="noindex, nofollow" />', false);
    }

    public function test_header_settings_from_admin_are_applied(): void
    {
        $h = Header::current();
        $h->update(['show_cta' => false, 'show_floating_button' => false, 'is_sticky' => false, 'background_style' => 'cream']);
        $html = $this->get('/about')->getContent();
        $this->assertStringNotContainsString('class="rsvp-btn"', $html);
        $this->assertStringNotContainsString('side-widgets', $html);
        $this->assertStringContainsString('header--static', $html);
        $this->assertStringContainsString('header--cream', $html);

        $h->update(['is_visible' => false]);
        $html = $this->get('/about')->getContent();
        $this->assertStringNotContainsString('<header', $html);
        $this->assertStringContainsString('no-header', $html);
        $this->assertStringContainsString('page-banner', $html);

        $h->update(['is_visible' => true, 'show_cta' => true, 'cta_label' => 'Book Now', 'cta_url' => '/contact']);
        $this->get('/about')->assertSee('class="rsvp-btn">Book Now</a>', false);
    }

    public function test_menu_edits_and_active_link(): void
    {
        NavigationMenu::create(['location' => 'header_right', 'label' => 'Journal', 'url' => '/journal', 'sort_order' => 9]);
        NavigationMenu::where('label', 'Shop')->first()->update(['is_visible' => false]);
        $html = $this->get('/about')->getContent();
        $this->assertStringContainsString('>Journal</a>', $html);
        $this->assertStringNotContainsString('>Shop</a>', $html);
        $this->assertMatchesRegularExpression('~<a href="/about" class="nav__link active">About</a>~', $html);
        $this->assertDoesNotMatchRegularExpression('~<a href="/portfolio" class="nav__link active">~', $html);
    }

    public function test_services_page_lists_services_or_empty_state(): void
    {
        $this->get('/services')->assertOk()->assertSee('Our services will be listed here shortly.');
        Service::create(['title' => 'Albums', 'short_description' => 'Hand-bound albums']);
        $this->get('/services')->assertOk()->assertSee('Albums')->assertSee('Hand-bound albums')->assertDontSee('listed here shortly');
    }

    public function test_page_blocks_rich_text_and_escaping(): void
    {
        $page = Page::create(['title' => 'Packages <script>alert(1)</script>', 'slug' => 'packages', 'template' => 'default',
            'content' => '<p>Hello <strong>world</strong></p><script>alert("xss")</script>']);
        $page->sections()->createMany([
            ['type' => 'text', 'heading' => 'Classic', 'body' => '<p>Details here</p>', 'sort_order' => 1],
            ['type' => 'video', 'heading' => 'Our film', 'video_url' => 'https://vimeo.com/76979871', 'sort_order' => 2],
            ['type' => 'cta', 'heading' => 'Ready?', 'button_label' => 'Contact', 'button_url' => '/contact', 'sort_order' => 3],
            ['type' => 'text', 'heading' => 'Secret block', 'is_visible' => false, 'sort_order' => 4],
        ]);

        $html = $this->get('/packages')->assertOk()->getContent();
        $this->assertStringContainsString('Hello <strong>world</strong>', $html);
        $this->assertStringNotContainsString('alert("xss")', $html, 'script in rich text is removed');
        $this->assertStringNotContainsString('<script>alert(1)', $html, 'title is escaped');
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        $this->assertStringContainsString('Details here', $html);
        $this->assertStringContainsString('player.vimeo.com/video/76979871', $html);
        $this->assertStringContainsString('>Contact</a>', $html);
        $this->assertStringNotContainsString('Secret block', $html);
    }

    public function test_unpublished_missing_and_unknown_pages_show_custom_404(): void
    {
        $this->get('/privacy-policy')->assertNotFound()->assertSee('This page has wandered off')->assertSee('Back to Home');
        $this->get('/definitely-not-a-page')->assertNotFound()->assertSee('404');
        $this->get('/a/b/c')->assertNotFound();

        Page::where('slug', 'privacy-policy')->first()->update(['is_published' => true, 'content' => '<p>Our real policy.</p>']);
        $this->get('/privacy-policy')->assertOk()->assertSee('Our real policy.', false);

        $this->getJson('/api/v1/nothing')->assertNotFound()->assertJsonStructure(['message']);
    }

    public function test_sitemap_and_robots(): void
    {
        $xml = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')->getContent();
        $this->assertStringContainsString('<loc>'.url('/').'</loc>', $xml);
        $this->assertStringContainsString('<loc>'.url('/about').'</loc>', $xml);
        $this->assertStringNotContainsString('privacy-policy', $xml, 'unpublished pages excluded');

        Page::where('slug', 'contact')->first()->seo()->updateOrCreate([], ['noindex' => true]);
        $cat = GalleryCategory::create(['name' => 'Candid']);
        GalleryImage::create(['gallery_category_id' => $cat->id, 'path' => UploadedFile::fake()->image('c.jpg', 900, 600)->store('gallery', 'public')]);
        GalleryCategory::create(['name' => 'Empty Category']);
        $xml = $this->get('/sitemap.xml')->getContent();
        $this->assertStringNotContainsString(url('/contact'), $xml, 'noindex pages excluded');
        $this->assertStringContainsString(url('/gallery/candid'), $xml);
        $this->assertStringNotContainsString('empty-category', $xml);
        simplexml_load_string($xml) ?: $this->fail('sitemap must be valid XML');

        $robots = $this->get('/robots.txt')->assertOk()->getContent();
        $this->assertStringContainsString('Disallow: /admin', $robots);
        $this->assertStringContainsString('Sitemap: '.url('/sitemap.xml'), $robots);
    }

    public function test_every_internal_link_on_key_pages_resolves(): void
    {
        Setting::setMany(['social.instagram' => 'https://instagram.com/example']);
        $broken = [];
        foreach (['/', '/about', '/services', '/contact'] as $start) {
            preg_match_all('~href="(/[^"#?]*)"~', $this->get($start)->getContent(), $m);
            foreach (array_unique($m[1]) as $path) {
                if (str_starts_with($path, '/storage') || str_starts_with($path, '/css') || str_starts_with($path, '/js') || str_starts_with($path, '/images')) {
                    continue;
                }
                // /gallery/{category}, /blog/{post} detail pages arrive in the next PR
                if (substr_count(trim($path, '/'), '/') >= 1) {
                    continue;
                }
                $status = $this->get($path)->getStatusCode();
                if ($status !== 200) {
                    $broken[] = "$start → $path ($status)";
                }
            }
        }
        $this->assertSame([], $broken, 'broken internal links: '.implode(', ', $broken));
    }

    public function test_image_resizer_caches_and_leaves_external_urls_alone(): void
    {
        $path = UploadedFile::fake()->image('big.jpg', 3000, 2000)->store('hero', 'public');
        $url = Storage::disk('public')->url($path);
        $resized = img_url($url, 800);
        $this->assertStringContainsString('/_cache/800/hero/', $resized);
        $file = str($resized)->after('/storage/')->toString();
        Storage::disk('public')->assertExists($file);
        [$w] = getimagesizefromstring(Storage::disk('public')->get($file));
        $this->assertSame(800, $w);
        $this->assertSame($resized, img_url($url, 800), 'second call reuses the cached file');
        $this->assertSame('https://example.com/a.jpg', img_url('https://example.com/a.jpg', 800));
        $this->assertSame('/images/logo.png', img_url('/images/logo.png', 600));
        $this->assertNull(img_url(null));
    }

    public function test_pages_render_from_a_serializing_cache_store(): void
    {
        config(['cache.default' => 'database']);
        foreach (['/', '/about', '/services', '/contact'] as $url) {
            $this->get($url)->assertOk();
            $this->get($url)->assertOk(); // second request served from cache
        }
    }
}
