<?php

namespace Tests\Feature;

use App\Filament\Resources\GalleryCategories\Pages\CreateGalleryCategory;
use App\Filament\Resources\GalleryImages\Pages\CreateGalleryImage;
use App\Filament\Resources\GalleryImages\Pages\EditGalleryImage;
use App\Filament\Resources\GalleryImages\Pages\ListGalleryImages;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Videos\Pages\CreateVideo;
use App\Models\GalleryCategory;
use App\Models\GalleryImage;
use App\Models\User;
use App\Models\Video;
use Database\Seeders\RolesAndAdminSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class GalleryVideoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->seed(RolesAndAdminSeeder::class);
        Filament::setCurrentPanel('admin');
    }

    private function admin(string $role = 'super-admin'): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        return $user;
    }

    public function test_admin_login_and_logout_work(): void
    {
        $user = $this->admin();
        $user->update(['password' => 'Secret-pass-123']);

        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/admin/login')->assertOk();

        $this->actingAs($user)->get('/admin')->assertOk();
        $this->actingAs($user)->post('/admin/logout')->assertRedirect();
        $this->assertGuest();
        $this->get('/admin/gallery-images')->assertRedirect('/admin/login');
    }

    public function test_login_form_accepts_valid_and_rejects_invalid_credentials(): void
    {
        $user = $this->admin();
        $user->update(['password' => 'Secret-pass-123']);

        Livewire::test(\Filament\Auth\Pages\Login::class)
            ->fillForm(['email' => $user->email, 'password' => 'wrong-password'])
            ->call('authenticate')->assertHasFormErrors(['email']);
        $this->assertGuest();

        Livewire::test(\Filament\Auth\Pages\Login::class)
            ->fillForm(['email' => $user->email, 'password' => 'Secret-pass-123'])
            ->call('authenticate')->assertHasNoFormErrors();
        $this->assertAuthenticatedAs($user);
    }

    public function test_inactive_or_roleless_users_cannot_enter_admin(): void
    {
        $roleless = User::factory()->create();
        $this->actingAs($roleless)->get('/admin')->assertForbidden();

        $inactive = $this->admin();
        $inactive->update(['is_active' => false]);
        $this->actingAs($inactive)->get('/admin')->assertForbidden();
    }

    public function test_admin_can_create_category_and_upload_multiple_images(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(CreateGalleryCategory::class)
            ->fillForm(['name' => 'Haldi Ceremony', 'is_published' => true])
            ->call('create')->assertHasNoFormErrors();

        $category = GalleryCategory::where('slug', 'haldi-ceremony')->firstOrFail();

        Livewire::test(CreateGalleryImage::class)
            ->fillForm([
                'gallery_category_id' => $category->id,
                'uploads' => [
                    UploadedFile::fake()->image('a.jpg', 2000, 1200),
                    UploadedFile::fake()->image('b.png', 800, 600),
                    UploadedFile::fake()->image('c.jpg', 1000, 1500),
                ],
                'is_published' => true,
            ])
            ->call('create')->assertHasNoFormErrors();

        $this->assertSame(3, $category->images()->count());
        $image = $category->images()->first();
        Storage::disk('public')->assertExists($image->path);
        $this->assertNotEmpty($image->variants, 'responsive WebP variants should be generated');
        foreach ($image->variants as $variant) {
            Storage::disk('public')->assertExists($variant);
        }
        $this->assertSame(2000, $image->width);
        // Smaller image must not be upscaled
        $small = $category->images()->where('width', 800)->first();
        $this->assertArrayNotHasKey(960, $small->variants);
    }

    public function test_invalid_file_types_are_rejected(): void
    {
        $this->actingAs($this->admin());
        $category = GalleryCategory::create(['name' => 'X']);

        Livewire::test(CreateGalleryImage::class)
            ->fillForm([
                'gallery_category_id' => $category->id,
                'uploads' => [UploadedFile::fake()->create('evil.php', 10, 'application/x-php')],
            ])
            ->call('create')->assertHasFormErrors(['uploads']);

        $this->assertSame(0, GalleryImage::count());
    }

    public function test_edit_replace_and_delete_image_removes_files(): void
    {
        $this->actingAs($this->admin());
        $category = GalleryCategory::create(['name' => 'Portraits']);
        $image = GalleryImage::create(['gallery_category_id' => $category->id, 'path' => UploadedFile::fake()->image('o.jpg', 1200, 800)->store('gallery', 'public')]);
        $oldPath = $image->path;
        $oldVariants = $image->variants;

        // The UI swaps the single-file field: remove the old file, then add the new one.
        Livewire::test(EditGalleryImage::class, ['record' => $image->getRouteKey()])
            ->fillForm(['path' => null])
            ->fillForm([
                'title' => 'Golden hour',
                'alt_text' => 'Couple at sunset',
                'path' => [UploadedFile::fake()->image('new.jpg', 1000, 700)],
            ])
            ->call('save')->assertHasNoFormErrors();

        $image->refresh();
        $this->assertSame('Golden hour', $image->title);
        $this->assertNotSame($oldPath, $image->path);
        Storage::disk('public')->assertMissing($oldPath);
        foreach ($oldVariants as $v) {
            Storage::disk('public')->assertMissing($v);
        }

        $path = $image->path;
        $variants = $image->variants;
        $image->delete();
        Storage::disk('public')->assertMissing($path);
        foreach ($variants as $v) {
            Storage::disk('public')->assertMissing($v);
        }
    }

    public function test_gallery_api_only_returns_published_content_and_updates_instantly(): void
    {
        $cat = GalleryCategory::create(['name' => 'Wedding Photography']);
        $hidden = GalleryCategory::create(['name' => 'Hidden', 'is_published' => false]);
        $pub = GalleryImage::create(['gallery_category_id' => $cat->id, 'title' => 'Vows', 'path' => UploadedFile::fake()->image('1.jpg', 1200, 800)->store('gallery', 'public'), 'is_featured' => true]);
        GalleryImage::create(['gallery_category_id' => $cat->id, 'title' => 'Draft', 'is_published' => false, 'path' => UploadedFile::fake()->image('2.jpg')->store('gallery', 'public')]);
        GalleryImage::create(['gallery_category_id' => $hidden->id, 'title' => 'Secret', 'path' => UploadedFile::fake()->image('3.jpg')->store('gallery', 'public')]);

        $this->getJson('/api/v1/gallery/categories')->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'wedding-photography')
            ->assertJsonPath('data.0.images_count', 1);

        $this->getJson('/api/v1/gallery/images')->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Vows')
            ->assertJsonStructure(['data' => [['id', 'url', 'thumb_url', 'srcset', 'width', 'height']], 'meta' => ['total', 'per_page']]);

        $this->getJson('/api/v1/gallery/categories/hidden')->assertNotFound();
        $this->getJson('/api/v1/gallery/images?category=wedding-photography&featured=1')->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/gallery/images?search=zzz')->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/gallery/images?per_page=9999')->assertStatus(422);

        // Admin edits → API reflects it immediately (cache invalidated).
        $pub->update(['title' => 'Vows (edited)']);
        $this->getJson('/api/v1/gallery/images')->assertJsonPath('data.0.title', 'Vows (edited)');
        $pub->delete();
        $this->getJson('/api/v1/gallery/images')->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/gallery/categories')->assertJsonPath('data.0.images_count', 0);
    }

    public function test_video_via_youtube_and_vimeo_links(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(CreateVideo::class)
            ->fillForm(['title' => 'Aarav & Mira Highlights', 'source_type' => 'youtube', 'video_url' => 'https://youtu.be/dQw4w9WgXcQ', 'is_featured' => true])
            ->call('create')->assertHasNoFormErrors();

        Livewire::test(CreateVideo::class)
            ->fillForm(['title' => 'Bad link', 'source_type' => 'youtube', 'video_url' => 'https://example.com/video'])
            ->call('create')->assertHasFormErrors(['video_url']);

        Video::create(['title' => 'Vimeo film', 'source_type' => 'youtube', 'video_url' => 'https://vimeo.com/76979871']);

        $yt = Video::where('title', 'Aarav & Mira Highlights')->firstOrFail();
        $this->assertSame('youtube', $yt->source_type);
        $this->assertSame('dQw4w9WgXcQ', $yt->external_id);
        $this->assertSame('vimeo', Video::where('title', 'Vimeo film')->first()->source_type);

        $this->getJson('/api/v1/videos?featured=1')->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.embed_url', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?rel=0&modestbranding=1')
            ->assertJsonPath('data.0.thumbnail_url', 'https://img.youtube.com/vi/dQw4w9WgXcQ/hqdefault.jpg')
            ->assertJsonPath('data.0.file_url', null);
        $this->getJson('/api/v1/videos')->assertJsonCount(2, 'data');
        $this->getJson('/api/v1/videos/'.$yt->slug)->assertOk()->assertJsonPath('data.title', 'Aarav & Mira Highlights');
    }

    public function test_video_upload_with_thumbnail_and_unpublish(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(CreateVideo::class)
            ->fillForm([
                'title' => 'Cinematic Teaser',
                'source_type' => 'upload',
                'video_path' => UploadedFile::fake()->create('teaser.mp4', 2048, 'video/mp4'),
                'thumbnail_path' => UploadedFile::fake()->image('thumb.jpg', 640, 360),
            ])
            ->call('create')->assertHasNoFormErrors();

        $video = Video::firstOrFail();
        $this->assertSame('upload', $video->source_type);
        Storage::disk('public')->assertExists($video->video_path);
        Storage::disk('public')->assertExists($video->thumbnail_path);

        $this->getJson('/api/v1/videos')->assertJsonPath('data.0.file_url', $video->file_url)
            ->assertJsonPath('data.0.embed_url', null);

        $video->update(['is_published' => false]);
        $this->getJson('/api/v1/videos')->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/videos/'.$video->slug)->assertNotFound();

        $video->delete();
        Storage::disk('public')->assertMissing($video->video_path);
    }

    public function test_video_upload_rejects_non_video_files(): void
    {
        $this->actingAs($this->admin());
        Livewire::test(CreateVideo::class)
            ->fillForm(['title' => 'x', 'source_type' => 'upload', 'video_path' => UploadedFile::fake()->create('x.exe', 10, 'application/octet-stream')])
            ->call('create')->assertHasFormErrors(['video_path']);
    }

    public function test_permissions_editor_cannot_manage_users_and_enquiry_handler_cannot_manage_gallery(): void
    {
        $this->actingAs($this->admin('editor'));
        $this->get('/admin/gallery-images')->assertOk();
        $this->get('/admin/users')->assertForbidden();

        $this->actingAs($this->admin('enquiry-handler'));
        $this->get('/admin/gallery-images')->assertForbidden();
        $this->get('/admin/videos')->assertForbidden();

        $this->actingAs($this->admin('super-admin'));
        $this->get('/admin/users')->assertOk();
    }

    public function test_deleting_category_removes_its_images_and_files(): void
    {
        $cat = GalleryCategory::create(['name' => 'Temp']);
        $img = GalleryImage::create(['gallery_category_id' => $cat->id, 'path' => UploadedFile::fake()->image('t.jpg', 900, 600)->store('gallery', 'public')]);
        $path = $img->path;
        $cat->delete();
        $this->assertSame(0, GalleryImage::count());
        Storage::disk('public')->assertMissing($path);
    }
}
