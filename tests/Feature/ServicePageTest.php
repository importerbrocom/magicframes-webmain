<?php

namespace Tests\Feature;

use App\Filament\Resources\Services\Pages\EditService;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\RolesAndAdminSeeder;
use Database\Seeders\SiteContentSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ServicePageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->seed(RolesAndAdminSeeder::class);
        $this->seed(SiteContentSeeder::class);
        Filament::setCurrentPanel('admin');
    }

    public function test_service_page_shows_details_photos_videos_and_faq(): void
    {
        Storage::disk('public')->put('services/gallery/a.jpg', 'x');
        $svc = Service::create([
            'title' => 'Wedding Photography', 'short_description' => 'Full-day coverage', 'description' => '<p>Long details here</p>', 'is_published' => true,
            'highlights' => ['Two photographers'],
            'faqs' => [['question' => 'How to book?', 'answer' => 'Call us']],
            'gallery' => [['image' => 'services/gallery/a.jpg', 'title' => 'Haldi', 'alt' => 'Haldi alt']],
            'videos' => [
                ['title' => 'Teaser', 'source' => 'link', 'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'],
                ['title' => 'Bad', 'source' => 'link', 'url' => 'https://example.com/x'],
            ],
        ]);
        $this->get('/services/wedding-photography')->assertOk()
            ->assertSee('Long details here', false)->assertSee('Two photographers')->assertSee('Haldi alt', false)
            ->assertSee('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', false)->assertSee('How to book?')
            ->assertSee('FAQPage', false)->assertSee('/contact?service=wedding-photography', false)->assertDontSee('>Bad<', false);

        $this->get('/services')->assertOk()->assertSee('href="/services/wedding-photography"', false);
        $this->get('/sitemap.xml')->assertOk()->assertSee('/services/wedding-photography');
        $this->get('/contact?service=wedding-photography')->assertOk()->assertSee('I am interested in Wedding Photography.');

        $svc->update(['is_published' => false]);
        $this->get('/services/wedding-photography')->assertNotFound();
        $this->get('/services/nothing-here')->assertNotFound();
    }

    public function test_service_without_extras_still_renders(): void
    {
        Service::create(['title' => 'Albums', 'is_published' => true]);
        $this->get('/services/albums')->assertOk()->assertSee('Albums')->assertDontSee('Photos<', false)->assertSee('Enquire now');
    }

    public function test_admin_edits_service_extras_and_removed_files_are_deleted(): void
    {
        $u = User::factory()->create(['is_active' => true]);
        $u->assignRole('super-admin');
        $this->actingAs($u);
        $svc = Service::create(['title' => 'Films', 'is_published' => true]);

        Livewire::test(EditService::class, ['record' => $svc->getRouteKey()])->fillForm([
            'description' => '<p>About films</p>',
            'highlights' => [['text' => 'Highlight reel']],
            'gallery' => [['image' => [UploadedFile::fake()->image('p.jpg', 600, 400)], 'title' => 'One', 'alt' => 'alt one']],
            'videos' => [['title' => 'Trailer', 'source' => 'link', 'url' => 'https://youtu.be/dQw4w9WgXcQ']],
            'faqs' => [['question' => 'Q?', 'answer' => 'A.']],
        ])->call('save')->assertHasNoFormErrors();

        $svc->refresh();
        $this->assertSame('Highlight reel', $svc->highlights[0]);
        $path = $svc->gallery[0]['image'];
        Storage::disk('public')->assertExists($path);
        $this->get('/services/films')->assertOk()->assertSee('About films', false)->assertSee('Trailer');

        Livewire::test(EditService::class, ['record' => $svc->getRouteKey()])
            ->fillForm(['videos' => [['title' => 'Bad', 'source' => 'link', 'url' => 'https://example.com/nope']]])
            ->call('save')->assertHasFormErrors();

        $svc->update(['gallery' => []]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_demo_install_gives_wedding_service_a_full_sample_page_and_remove_cleans_up(): void
    {
        $this->artisan('demo:install', ['--force' => true])->assertSuccessful();
        $svc = Service::where('slug', 'wedding-photography')->firstOrFail();
        $this->assertCount(8, $svc->gallery);
        $this->assertCount(3, $svc->videos);
        $files = $svc->detailFiles();
        $this->assertNotEmpty($files);
        foreach ($files as $f) {
            Storage::disk('public')->assertExists($f);
        }
        $html = $this->get('/services/wedding-photography')->assertOk()->getContent();
        $this->assertSame(8, substr_count($html, 'data-lightbox'));
        $this->assertSame(3, preg_match_all('/ data-video /', $html));
        $this->assertStringContainsString('youtube-nocookie.com/embed/aqz-KE-bpKQ', $html);
        $this->assertStringContainsString('Questions', $html);

        $this->artisan('demo:remove')->assertSuccessful();
        foreach ($files as $f) {
            Storage::disk('public')->assertMissing($f);
        }
    }
}
