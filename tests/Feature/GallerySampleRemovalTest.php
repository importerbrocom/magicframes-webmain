<?php

namespace Tests\Feature;

use App\Filament\Resources\GalleryImages\Pages\ListGalleryImages;
use App\Models\GalleryCategory;
use App\Models\GalleryImage;
use App\Models\HeroSlide;
use App\Models\User;
use App\Services\DemoContent;
use Database\Seeders\GalleryStructureSeeder;
use Database\Seeders\RolesAndAdminSeeder;
use Database\Seeders\SiteContentSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class GallerySampleRemovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_newest_first_and_remove_only_sample_photos(): void
    {
        Storage::fake('public');
        $this->seed([RolesAndAdminSeeder::class, GalleryStructureSeeder::class, SiteContentSeeder::class]);
        Filament::setCurrentPanel('admin');
        $u = User::factory()->create(['is_active' => true]);
        $u->assignRole('super-admin');
        $this->actingAs($u);

        $this->artisan('demo:install', ['--force' => true])->assertSuccessful();
        $this->assertGreaterThan(0, GalleryImage::count());
        $slides = HeroSlide::count();

        $cat = GalleryCategory::where('slug', 'wedding-photography')->first();
        Storage::disk('public')->put('gallery/mine.jpg', 'x');
        $this->travel(1)->minutes();
        $mine = GalleryImage::create(['gallery_category_id' => $cat->id, 'title' => 'My own photo', 'path' => 'gallery/mine.jpg', 'is_published' => true, 'sort_order' => 99]);

        // newest first: my upload is the first row; "Only my photos" filter shows just it
        Livewire::test(ListGalleryImages::class)->assertCanSeeTableRecords([$mine], inOrder: true)
            ->filterTable('sample', false)->assertCountTableRecords(1);

        Livewire::test(ListGalleryImages::class)->callAction('removeSamples')->assertNotified();

        $this->assertSame([$mine->id], GalleryImage::pluck('id')->all());
        Storage::disk('public')->assertExists('gallery/mine.jpg');
        $this->assertSame($slides, HeroSlide::count());                  // rest of the demo untouched
        $this->assertSame([], app(DemoContent::class)->sampleIds(GalleryImage::class));
        Livewire::test(ListGalleryImages::class)->assertActionHidden('removeSamples');
        $this->get('/gallery/wedding-photography')->assertOk()->assertSee('My own photo');

        // a later demo:remove still works and keeps my photo
        $this->artisan('demo:remove')->assertSuccessful();
        $this->assertTrue(GalleryImage::whereKey($mine->id)->exists());
    }

    public function test_stale_manifest_never_deletes_own_photos(): void
    {
        Storage::fake('public');
        $this->seed([RolesAndAdminSeeder::class, GalleryStructureSeeder::class, SiteContentSeeder::class]);
        $cat = GalleryCategory::first();
        $mine = GalleryImage::create(['gallery_category_id' => $cat->id, 'title' => 'Mine', 'path' => 'gallery/mine.jpg', 'is_published' => true]);
        // a leftover manifest that (wrongly) lists my photo's id
        file_put_contents(DemoContent::manifestPath(), json_encode(['created' => [GalleryImage::class => [$mine->id]], 'updated' => [], 'settings' => []]));

        $this->assertSame([], app(DemoContent::class)->sampleIds(GalleryImage::class));
        $this->assertSame(0, app(DemoContent::class)->removeOnly(GalleryImage::class));
        app(DemoContent::class)->remove();
        $this->assertTrue(GalleryImage::whereKey($mine->id)->exists());
    }
}
