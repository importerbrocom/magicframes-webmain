<?php

namespace Tests\Feature;

use App\Models\GalleryCategory;
use App\Models\GalleryImage;
use App\Models\HomeSection;
use Database\Seeders\GalleryStructureSeeder;
use Database\Seeders\RolesAndAdminSeeder;
use Database\Seeders\SiteContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HomePortfolioTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->seed([RolesAndAdminSeeder::class, GalleryStructureSeeder::class, SiteContentSeeder::class]);
        HomeSection::where('key', 'portfolio')->update(['is_visible' => true]);
    }

    public function test_portfolio_shows_newest_photos_when_none_are_featured(): void
    {
        $cat = GalleryCategory::where('slug', 'wedding-photography')->first();
        GalleryImage::create(['gallery_category_id' => $cat->id, 'title' => 'Not featured', 'alt_text' => 'Own photo alt', 'path' => 'gallery/a.jpg', 'is_published' => true, 'is_featured' => false]);

        $this->get('/')->assertOk()->assertSee('class="gallery__item"', false)->assertSee('Own photo alt');
    }

    public function test_portfolio_falls_back_to_category_covers(): void
    {
        Storage::disk('public')->put('gallery/covers/c.jpg', 'x');
        GalleryCategory::where('slug', 'engagement')->update(['cover_path' => 'gallery/covers/c.jpg', 'is_published' => true]);

        $this->get('/')->assertOk()->assertSee('href="/gallery/engagement"', false)->assertSee('class="gallery__item"', false);
    }

    public function test_when_photos_are_featured_only_those_show(): void
    {
        $cat = GalleryCategory::first();
        GalleryImage::create(['gallery_category_id' => $cat->id, 'alt_text' => 'Plain one', 'path' => 'gallery/p.jpg', 'is_published' => true]);
        GalleryImage::create(['gallery_category_id' => $cat->id, 'alt_text' => 'Star one', 'path' => 'gallery/s.jpg', 'is_published' => true, 'is_featured' => true]);

        $this->get('/')->assertOk()->assertSee('Star one')->assertDontSee('Plain one');
    }
}
