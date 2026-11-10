<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\GalleryImage;
use App\Models\HeroSlide;
use App\Models\HomeSection;
use App\Models\Page;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Testimonial;
use App\Models\Video;
use Database\Seeders\RolesAndAdminSeeder;
use Database\Seeders\SiteContentSeeder;
use Database\Seeders\GalleryStructureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DemoContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->seed([RolesAndAdminSeeder::class, GalleryStructureSeeder::class, SiteContentSeeder::class]);
        File::delete(storage_path('app/demo-manifest.json'));
    }

    protected function tearDown(): void
    {
        File::delete(storage_path('app/demo-manifest.json'));
        parent::tearDown();
    }

    public function test_install_fills_every_part_of_the_website(): void
    {
        $this->artisan('demo:install')->assertSuccessful();

        $this->assertSame(3, HeroSlide::count());
        $this->assertSame(6, Service::count());
        $this->assertSame(3, Testimonial::count());
        $this->assertSame(27, GalleryImage::count());
        $this->assertSame(4, Video::count());
        $this->assertSame(4, BlogPost::count());

        $home = $this->getJson('/api/v1/home')->json('data');
        $keys = array_column($home['sections'], 'key');
        foreach (['hero', 'about', 'quote', 'services', 'stats', 'portfolio', 'videos', 'recent', 'testimonials', 'social', 'contact'] as $k) {
            $this->assertContains($k, $keys, "home block $k should be visible");
        }
        $this->assertCount(3, $home['hero']);

        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('Timeless Moments', $html);
        $this->assertStringContainsString('Sample Client One', $html);
        $this->assertStringContainsString('+00 000 000 0000', $html);

        $this->get('/gallery')->assertOk()->assertSee('data-lightbox', false);
        $this->get('/gallery/wedding-photography')->assertOk();
        $this->get('/videos')->assertOk()->assertSee('Sample cinematic film (placeholder video)');
        $this->get('/blog')->assertOk()->assertSee('Sample article');
        $this->get('/about')->assertOk()->assertSee('Sample: image with text');
        $this->assertNotNull(Page::where('slug', 'contact')->value('banner_image_path'));
        $this->getJson('/api/v1/gallery/images?featured=1')->assertOk()->assertJsonCount(6, 'data');
    }

    public function test_install_needs_no_internet_and_uses_the_bundled_kerala_photos(): void
    {
        \Illuminate\Support\Facades\Http::preventStrayRequests();
        $this->artisan('demo:install')->assertSuccessful();

        $this->assertSame(27, GalleryImage::count());
        $first = GalleryImage::where('title', 'Exchange of garlands')->firstOrFail();
        Storage::disk('public')->assertExists($first->path);
        $this->assertNotEmpty($first->variants);
        $this->assertGreaterThanOrEqual(1600, max($first->width, $first->height), 'HD source');
        $this->assertStringContainsString('Wikimedia Commons', $first->description, 'photographer + licence shown as caption');
        $this->assertStringContainsString('Kerala', $first->alt_text.' '.$first->category->description.' '.HeroSlide::first()->subheading);

        // every bundled photo is HD, credited, and every credit entry has its file
        $credits = json_decode(file_get_contents(database_path('demo-images/credits.json')), true);
        $this->assertCount(27, $credits);
        foreach ($credits as $key => $c) {
            $file = database_path('demo-images/'.$c['file']);
            $this->assertFileExists($file);
            [$w, $h] = getimagesize($file);
            $this->assertGreaterThanOrEqual(1300, min($w, $h), "$key should be HD");
            $this->assertGreaterThanOrEqual(1995, max($w, $h), "$key long side ~2000px");
            foreach (['author', 'license', 'source', 'caption', 'alt'] as $field) {
                $this->assertNotEmpty($c[$field], "$key missing $field");
            }
            $this->assertMatchesRegularExpression('/CC BY|Public domain|CC0/', $c['license']);
        }

        // visible attribution on the About page while the demo is installed
        $about = $this->get('/about')->assertOk()->getContent();
        $this->assertStringContainsString('Sample photo credits', $about);
        $this->assertStringContainsString('Gnoeee', $about);
        $this->assertStringContainsString('commons.wikimedia.org', $about);

        // sample testimonials never carry a stranger's photograph
        $this->assertSame(0, Testimonial::whereNotNull('photo_path')->count());
        // placeholder videos get wedding thumbnails and clear labels
        $this->assertStringContainsString('placeholder video', Video::first()->title);
        $this->assertNotNull(Video::first()->thumbnail_path);
    }

    public function test_remove_restores_the_site_and_never_touches_studio_content(): void
    {
        // the studio's own work, entered before the demo
        Service::create(['title' => 'Real Service', 'short_description' => 'Mine']);
        HomeSection::where('key', 'about')->first()->update(['title' => 'My own about title', 'body' => 'My own story']);
        Setting::setMany(['contact.phone' => '+91 111 222 333']);
        $before = HomeSection::where('key', 'contact')->first()->only(['title', 'is_visible', 'body']);

        $this->artisan('demo:install')->assertSuccessful();
        $this->assertSame('+00 000 000 0000', Setting::get('contact.phone'));
        $this->assertTrue(HomeSection::where('key', 'stats')->first()->is_visible);

        $this->artisan('demo:remove')->assertSuccessful();

        $this->assertSame(0, HeroSlide::count());
        $this->assertSame(0, GalleryImage::count());
        $this->assertSame(0, Video::count());
        $this->assertSame(0, BlogPost::count());
        $this->assertSame(0, Testimonial::count());
        $this->assertSame(0, \App\Models\PageSection::count());
        $this->assertSame(['Real Service'], Service::pluck('title')->all(), 'studio content survives');
        $this->assertSame('My own about title', HomeSection::where('key', 'about')->value('title'));
        $this->assertSame('My own story', HomeSection::where('key', 'about')->value('body'));
        $this->assertNull(HomeSection::where('key', 'about')->value('image_path'));
        $this->assertSame('+91 111 222 333', Setting::get('contact.phone'), 'previous settings restored');
        $this->assertNull(Setting::get('contact.email'));
        $this->assertSame($before, HomeSection::where('key', 'contact')->first()->only(['title', 'is_visible', 'body']));
        $this->assertNull(Page::where('slug', 'contact')->value('banner_image_path'));
        $this->assertEmpty(Storage::disk('public')->allFiles(), 'every sample file is deleted');
        $this->assertFileDoesNotExist(storage_path('app/demo-manifest.json'));
        $this->getJson('/api/v1/gallery/images')->assertJsonCount(0, 'data');
        $this->get('/')->assertOk()->assertDontSee('Timeless Moments');
    }

    public function test_cannot_install_twice_without_force_and_remove_without_install_is_harmless(): void
    {
        $this->artisan('demo:remove')->assertSuccessful();

        $this->artisan('demo:install')->assertSuccessful();
        $this->artisan('demo:install')->assertFailed();
        $this->artisan('demo:install', ['--force' => true])->assertSuccessful();
        $this->assertSame(3, HeroSlide::count(), 'force reinstall does not duplicate');
        $this->assertSame(27, GalleryImage::count());
    }
}
