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
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
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

    private function jpeg(): string
    {
        $im = imagecreatetruecolor(400, 300);
        imagefill($im, 0, 0, imagecolorallocate($im, 120, 90, 80));
        ob_start();
        imagejpeg($im);

        return str_pad(ob_get_clean(), 2500, "\0"); // > 2000 bytes like a real photo
    }

    public function test_install_fills_every_part_of_the_website(): void
    {
        Http::fake(['picsum.photos/*' => Http::response($this->jpeg(), 200, ['Content-Type' => 'image/jpeg'])]);

        $this->artisan('demo:install')->assertSuccessful();

        $this->assertSame(3, HeroSlide::count());
        $this->assertSame(6, Service::count());
        $this->assertSame(3, Testimonial::count());
        $this->assertSame(40, GalleryImage::count());
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
        $this->get('/videos')->assertOk()->assertSee('Sample film: Sintel');
        $this->get('/blog')->assertOk()->assertSee('Sample article');
        $this->get('/about')->assertOk()->assertSee('Sample: image with text');
        $this->assertNotNull(Page::where('slug', 'contact')->value('banner_image_path'));
        $this->getJson('/api/v1/gallery/images?featured=1')->assertOk()->assertJsonCount(12, 'data');
    }

    public function test_install_works_offline_with_generated_photos(): void
    {
        Http::fake(fn () => throw new ConnectionException('offline'));
        $this->artisan('demo:install')->assertSuccessful();
        $this->assertSame(40, GalleryImage::count());
        $first = GalleryImage::first();
        Storage::disk('public')->assertExists($first->path);
        $this->assertNotEmpty($first->variants);
    }

    public function test_remove_restores_the_site_and_never_touches_studio_content(): void
    {
        Http::fake(['picsum.photos/*' => Http::response($this->jpeg(), 200, ['Content-Type' => 'image/jpeg'])]);

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
        Http::fake(['picsum.photos/*' => Http::response($this->jpeg(), 200, ['Content-Type' => 'image/jpeg'])]);
        $this->artisan('demo:remove')->assertSuccessful();

        $this->artisan('demo:install')->assertSuccessful();
        $this->artisan('demo:install')->assertFailed();
        $this->artisan('demo:install', ['--force' => true])->assertSuccessful();
        $this->assertSame(3, HeroSlide::count(), 'force reinstall does not duplicate');
        $this->assertSame(40, GalleryImage::count());
    }
}
