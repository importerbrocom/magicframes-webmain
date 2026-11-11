<?php

namespace Tests\Feature;

use App\Models\GalleryCategory;
use App\Models\GalleryImage;
use App\Services\ImageProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HdImagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_uploads_get_hd_sizes_and_the_lightbox_uses_the_biggest(): void
    {
        Storage::fake('public');
        $cat = GalleryCategory::create(['name' => 'Weddings']);
        $img = GalleryImage::create(['gallery_category_id' => $cat->id,
            'path' => UploadedFile::fake()->image('big.jpg', 3000, 2000)->store('gallery', 'public')]);

        $this->assertSame([640, 1280, 2048, 2560], array_map('intval', array_keys($img->variants)));
        $this->assertStringEndsWith('-2560.webp', $img->large_url);
        $this->assertStringEndsWith('-1280.webp', $img->thumb_url);
        $this->assertStringContainsString('2560w', $img->srcset);
        [$w] = getimagesizefromstring(Storage::disk('public')->get($img->variants[2560]));
        $this->assertSame(2560, $w);
    }

    public function test_small_originals_are_never_upscaled(): void
    {
        Storage::fake('public');
        $cat = GalleryCategory::create(['name' => 'Weddings']);
        $img = GalleryImage::create(['gallery_category_id' => $cat->id,
            'path' => UploadedFile::fake()->image('small.jpg', 1000, 700)->store('gallery', 'public')]);

        $this->assertSame([640], array_map('intval', array_keys($img->variants)));
        $this->assertSame($img->url, $img->large_url, 'lightbox shows the full original');
    }

    public function test_images_hd_command_rebuilds_old_low_res_versions(): void
    {
        Storage::fake('public');
        $cat = GalleryCategory::create(['name' => 'Weddings']);
        $path = UploadedFile::fake()->image('old.jpg', 2600, 1700)->store('gallery', 'public');
        $img = GalleryImage::create(['gallery_category_id' => $cat->id, 'path' => $path]);
        // pretend it was processed by the old version (480/960/1600)
        Storage::disk('public')->put('gallery/variants/old-480.webp', 'x');
        $img->forceFill(['variants' => [480 => 'gallery/variants/old-480.webp']])->saveQuietly();
        Storage::disk('public')->put('_cache/1900/hero/x.webp', 'x');

        $this->artisan('images:hd')->assertSuccessful();

        $img->refresh();
        $this->assertSame(ImageProcessor::WIDTHS, array_map('intval', array_keys($img->variants)));
        Storage::disk('public')->assertMissing('gallery/variants/old-480.webp');
        Storage::disk('public')->assertMissing('_cache/1900/hero/x.webp');
        Storage::disk('public')->assertExists($path);   // original untouched
    }
}
