<?php

namespace Tests\Feature;

use App\Filament\Pages\HeaderSettings;
use App\Filament\Pages\SiteSettings;
use App\Filament\Resources\HeroSlides\Pages\ManageHeroSlides;
use App\Filament\Resources\HomeSections\Pages\ManageHomeSections;
use App\Filament\Resources\NavigationMenus\Pages\ManageNavigationMenus;
use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Filament\Resources\Services\Pages\ManageServices;
use App\Filament\Resources\Statistics\Pages\ManageStatistics;
use App\Filament\Resources\Testimonials\Pages\ManageTestimonials;
use App\Models\GalleryCategory;
use App\Models\GalleryImage;
use App\Models\HomeSection;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Testimonial;
use App\Models\User;
use Database\Seeders\RolesAndAdminSeeder;
use Database\Seeders\SiteContentSeeder;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SiteContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->seed([RolesAndAdminSeeder::class, SiteContentSeeder::class]);
        Filament::setCurrentPanel('admin');
    }

    private function login(string $role = 'super-admin'): User
    {
        $u = User::factory()->create(['is_active' => true]);
        $u->assignRole($role);
        $this->actingAs($u);

        return $u;
    }

    public function test_seeder_does_not_invent_business_content(): void
    {
        $site = $this->getJson('/api/v1/site')->assertOk()->json('data');
        foreach (['phone', 'whatsapp', 'email', 'address', 'hours', 'map_embed_url'] as $field) {
            $this->assertNull($site['contact'][$field], "contact.$field must start empty");
        }
        $this->assertSame([], $site['social']);
        $this->getJson('/api/v1/services')->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/testimonials')->assertJsonCount(0, 'data');
        $home = $this->getJson('/api/v1/home')->json('data');
        $keys = collect($home['sections'])->pluck('key')->all();
        $this->assertNotContains('stats', $keys);
        $this->assertNotContains('testimonials', $keys);
        // legal pages are hidden until real text exists
        $this->getJson('/api/v1/pages/privacy-policy')->assertNotFound();
        $this->getJson('/api/v1/pages/about')->assertOk()->assertJsonPath('data.banner.label', 'Who We Are');
    }

    public function test_seeder_is_idempotent_and_keeps_admin_edits(): void
    {
        Page::where('slug', 'about')->update(['title' => 'Edited']);
        $this->seed(SiteContentSeeder::class);
        $this->assertSame('Edited', Page::where('slug', 'about')->value('title'));
        $this->assertSame(1, Page::where('slug', 'about')->count());
    }

    public function test_site_settings_save_and_appear_in_api(): void
    {
        $this->login();
        Livewire::test(SiteSettings::class)
            ->fillForm([
                'site' => ['name' => 'Magic Frames Studio'],
                'contact' => ['phone' => '+10000000000', 'email' => 'hello@example.test', 'address' => 'Example Street 1'],
                'social' => ['instagram' => 'https://instagram.com/example'],
            ])
            ->call('save')->assertHasNoFormErrors();

        $site = $this->getJson('/api/v1/site')->json('data');
        $this->assertSame('Magic Frames Studio', $site['name']);
        $this->assertSame('+10000000000', $site['contact']['phone']);
        $this->assertSame(['instagram' => 'https://instagram.com/example'], $site['social']);

        Livewire::test(SiteSettings::class)
            ->fillForm(['social' => ['instagram' => 'not a url']])
            ->call('save')->assertHasFormErrors();

        // clearing a field removes it
        Livewire::test(SiteSettings::class)->fillForm(['contact' => ['phone' => '']])->call('save');
        $this->assertNull($this->getJson('/api/v1/site')->json('data.contact.phone'));
    }

    public function test_header_settings_and_menu_management(): void
    {
        $this->login();
        $api = fn () => $this->getJson('/api/v1/site')->json('data');

        $this->assertSame('/contact', $api()['header']['cta']['url']);
        $this->assertSame(['Home', 'About', 'Portfolio'], collect($api()['menus']['left'])->pluck('label')->all());

        Livewire::test(HeaderSettings::class)
            ->fillForm([
                'is_sticky' => false, 'background_style' => 'solid', 'show_cta' => true,
                'cta_label' => 'Book Now', 'cta_url' => '/contact', 'show_floating_button' => false,
                'logo_path' => [UploadedFile::fake()->image('logo.png', 400, 200)],
            ])
            ->call('save')->assertHasNoFormErrors();

        $h = $api()['header'];
        $this->assertFalse($h['sticky']);
        $this->assertSame('solid', $h['background_style']);
        $this->assertSame('Book Now', $h['cta']['label']);
        $this->assertFalse($h['floating_button']);
        $this->assertStringContainsString('/storage/branding/', $h['logo_url']);

        Livewire::test(HeaderSettings::class)->fillForm(['show_cta' => false])->call('save');
        $this->assertNull($api()['header']['cta']);

        // add / hide / delete menu items
        $component = Livewire::test(ManageNavigationMenus::class)
            ->callAction(CreateAction::class, ['location' => 'header_right', 'label' => 'Videos', 'url' => '/videos', 'sort_order' => 9, 'is_visible' => true])
            ->assertHasNoFormErrors();
        $this->assertContains('Videos', collect($api()['menus']['right'])->pluck('label')->all());
        $this->assertContains('Videos', collect($api()['menus']['mobile'])->pluck('label')->all());

        $item = NavigationMenu::where('label', 'Videos')->where('location', 'header_right')->first();
        $item->update(['is_visible' => false]);
        $this->assertNotContains('Videos', collect($api()['menus']['right'])->pluck('label')->all());

        Livewire::test(ManageNavigationMenus::class)
            ->callAction(CreateAction::class, ['location' => 'header_left', 'label' => 'Bad', 'url' => 'javascript:alert(1)'])
            ->assertHasFormErrors(['url']);

        $item->delete();
        $this->assertDatabaseMissing('navigation_menus', ['id' => $item->id]);
    }

    public function test_menu_item_linked_to_page_follows_page_and_hides_when_unpublished(): void
    {
        $page = Page::create(['title' => 'Packages', 'slug' => 'packages', 'template' => 'default']);
        NavigationMenu::create(['location' => 'footer', 'label' => 'Packages', 'page_id' => $page->id]);
        $labels = fn () => collect($this->getJson('/api/v1/site')->json('data.menus.footer'))->pluck('url', 'label');
        $this->assertSame('/packages', $labels()['Packages']);

        $page->update(['slug' => 'our-packages']);
        $this->assertSame('/our-packages', $labels()['Packages']);

        $page->update(['is_published' => false]);
        $this->assertArrayNotHasKey('Packages', $labels()->all());
    }

    public function test_admin_can_create_and_edit_page_with_blocks_and_seo(): void
    {
        $this->login();

        Livewire::test(CreatePage::class)
            ->fillForm([
                'title' => 'Wedding Packages',
                'template' => 'default',
                'is_published' => true,
                'banner_label' => 'Plans',
                'content' => '<p>Intro text</p>',
                'sections' => [
                    ['type' => 'text', 'heading' => 'Classic', 'body' => '<p>Details</p>', 'is_visible' => true],
                    ['type' => 'video', 'heading' => 'Film', 'video_url' => 'https://youtu.be/dQw4w9WgXcQ', 'is_visible' => true],
                    ['type' => 'text', 'heading' => 'Hidden block', 'is_visible' => false],
                ],
                'seo' => ['meta_title' => 'Packages | Magic Frames', 'meta_description' => 'Our wedding packages.'],
            ])
            ->call('create')->assertHasNoFormErrors();

        $page = Page::where('slug', 'wedding-packages')->firstOrFail();
        $this->assertFalse($page->is_system);

        $json = $this->getJson('/api/v1/pages/wedding-packages')->assertOk()->json('data');
        $this->assertSame('Plans', $json['banner']['label']);
        $this->assertSame('<p>Intro text</p>', $json['content']);
        $this->assertCount(2, $json['sections'], 'hidden blocks are not exposed');
        $this->assertSame('Classic', $json['sections'][0]['heading']);
        $this->assertSame('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?rel=0&modestbranding=1', $json['sections'][1]['embed_url']);
        $this->assertSame('Packages | Magic Frames — Magic Frames', $json['seo']['title']);
        $this->assertSame('Our wedding packages.', $json['seo']['description']);

        // bad video link rejected
        Livewire::test(CreatePage::class)
            ->fillForm(['title' => 'Bad', 'template' => 'default', 'sections' => [['type' => 'video', 'video_url' => 'https://example.com/x']]])
            ->call('create')->assertHasFormErrors();

        // duplicate / reserved slugs rejected
        Livewire::test(CreatePage::class)->fillForm(['title' => 'X', 'slug' => 'about', 'template' => 'default'])
            ->call('create')->assertHasFormErrors(['slug']);
        Livewire::test(CreatePage::class)->fillForm(['title' => 'X', 'slug' => 'admin', 'template' => 'default'])
            ->call('create')->assertHasFormErrors(['slug']);

        // unpublish => 404 from the API
        Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
            ->fillForm(['is_published' => false])->call('save')->assertHasNoFormErrors();
        $this->getJson('/api/v1/pages/wedding-packages')->assertNotFound();
    }

    public function test_system_pages_cannot_be_deleted_or_renamed_in_url(): void
    {
        $this->login();
        $about = Page::where('slug', 'about')->first();

        Livewire::test(EditPage::class, ['record' => $about->getRouteKey()])
            ->assertActionHidden(DeleteAction::class);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $about->delete();
    }

    public function test_services_crud_and_ordering_reflect_in_api(): void
    {
        $this->login();
        Livewire::test(ManageServices::class)
            ->callAction(CreateAction::class, [
                'title' => 'Wedding Photography', 'short_description' => 'Full-day coverage', 'is_published' => true, 'sort_order' => 2,
                'image_path' => UploadedFile::fake()->image('s.jpg', 800, 600),
            ])->assertHasNoFormErrors();
        Livewire::test(ManageServices::class)
            ->callAction(CreateAction::class, ['title' => 'Albums', 'is_published' => true, 'sort_order' => 1])->assertHasNoFormErrors();
        Livewire::test(ManageServices::class)
            ->callAction(CreateAction::class, ['title' => 'Draft service', 'is_published' => false])->assertHasNoFormErrors();

        $list = $this->getJson('/api/v1/services')->assertOk()->json('data');
        $this->assertSame(['Albums', 'Wedding Photography'], array_column($list, 'title'));
        $this->assertNotNull($list[1]['image_url']);
        $this->getJson('/api/v1/services/albums')->assertOk();
        $this->getJson('/api/v1/services/draft-service')->assertNotFound();

        $svc = Service::where('slug', 'albums')->first();
        Livewire::test(ManageServices::class)
            ->callTableAction('edit', $svc, ['title' => 'Photo Albums'])->assertHasNoFormErrors();
        $this->assertSame('Photo Albums', $this->getJson('/api/v1/services')->json('data.0.title'));

        $withImage = Service::where('slug', 'wedding-photography')->first();
        $path = $withImage->image_path;
        Storage::disk('public')->assertExists($path);
        Livewire::test(ManageServices::class)->callTableAction('delete', $withImage);
        Storage::disk('public')->assertMissing($path);
        $this->getJson('/api/v1/services')->assertJsonCount(1, 'data');
    }

    public function test_testimonials_management(): void
    {
        $this->login();
        Livewire::test(ManageTestimonials::class)
            ->callAction(CreateAction::class, [
                'client_name' => 'Test Client', 'client_role' => 'Bride', 'rating' => 4, 'body' => 'Great work.',
                'photo_path' => UploadedFile::fake()->image('p.jpg', 300, 300), 'is_published' => true,
            ])->assertHasNoFormErrors();
        Livewire::test(ManageTestimonials::class)
            ->callAction(CreateAction::class, ['client_name' => '', 'body' => ''])->assertHasFormErrors(['client_name', 'body']);

        $t = $this->getJson('/api/v1/testimonials')->assertJsonCount(1, 'data')->json('data.0');
        $this->assertSame(4, $t['rating']);
        $this->assertNotNull($t['photo_url']);

        Testimonial::first()->update(['is_published' => false]);
        $this->getJson('/api/v1/testimonials')->assertJsonCount(0, 'data');
    }

    public function test_homepage_management_sections_hero_stats_and_featured_content(): void
    {
        $this->login();
        $home = fn () => $this->getJson('/api/v1/home')->assertOk()->json('data');

        // edit the About section
        $about = HomeSection::where('key', 'about')->first();
        Livewire::test(ManageHomeSections::class)
            ->callTableAction('edit', $about, ['title' => 'Our Story Title', 'body' => 'Real story text', 'image_path' => UploadedFile::fake()->image('a.jpg', 900, 600)])
            ->assertHasNoFormErrors();
        $s = collect($home()['sections'])->firstWhere('key', 'about');
        $this->assertSame('Our Story Title', $s['title']);
        $this->assertNotNull($s['image_url']);

        // hide + reorder
        // drag-and-drop reorder in the admin table (bulk SQL update) must refresh the API
        $order = HomeSection::orderBy('sort_order')->pluck('id')->all();
        $contactId = HomeSection::where('key', 'contact')->value('id');
        $order = array_merge([$contactId], array_values(array_diff($order, [$contactId])));
        $this->assertNotSame('contact', $home()['sections'][0]['key']);
        Livewire::test(ManageHomeSections::class)->call('reorderTable', $order);
        $this->assertSame('contact', $home()['sections'][0]['key']);
        HomeSection::where('key', 'contact')->first()->update(['is_visible' => false]);
        $this->assertNotContains('contact', array_column($home()['sections'], 'key'));

        // stats appear only when real figures exist
        HomeSection::where('key', 'stats')->first()->update(['is_visible' => true]);
        $this->assertNotContains('stats', array_column($home()['sections'], 'key'));
        Livewire::test(ManageStatistics::class)
            ->callAction(CreateAction::class, ['label' => 'Weddings', 'value' => 120, 'suffix' => '+', 'is_published' => true])->assertHasNoFormErrors();
        $stats = collect($home()['sections'])->firstWhere('key', 'stats');
        $this->assertSame([['label' => 'Weddings', 'value' => 120, 'suffix' => '+']], $stats['items']);

        // hero slides
        $this->assertSame([], $home()['hero']);
        Livewire::test(ManageHeroSlides::class)
            ->callAction(CreateAction::class, ['heading' => 'Timeless Frames', 'image_path' => UploadedFile::fake()->image('h.jpg', 1900, 900), 'is_published' => true])
            ->assertHasNoFormErrors();
        $this->assertSame('Timeless Frames', $home()['hero'][0]['heading']);

        // featured gallery images flow into the "portfolio" block
        $cat = GalleryCategory::create(['name' => 'Weddings']);
        GalleryImage::create(['gallery_category_id' => $cat->id, 'title' => 'Featured one', 'is_featured' => true, 'path' => UploadedFile::fake()->image('f.jpg', 900, 600)->store('gallery', 'public')]);
        GalleryImage::create(['gallery_category_id' => $cat->id, 'title' => 'Not featured', 'path' => UploadedFile::fake()->image('g.jpg', 900, 600)->store('gallery', 'public')]);
        $portfolio = collect($home()['sections'])->firstWhere('key', 'portfolio');
        $this->assertCount(1, $portfolio['items']);
        $this->assertSame('Featured one', $portfolio['items'][0]['title']);

        // the fixed block list cannot be extended from the admin, even by a super admin
        $this->get('/admin/home-sections/create')->assertNotFound();
    }

    public function test_permissions_for_settings_and_content_screens(): void
    {
        $this->login('editor');
        $this->get('/admin/pages')->assertOk();
        $this->get('/admin/services')->assertOk();
        $this->get('/admin/header-settings')->assertOk();
        $this->get('/admin/site-settings')->assertForbidden();

        $this->login('enquiry-handler');
        foreach (['pages', 'services', 'testimonials', 'header-settings', 'site-settings', 'home-sections', 'navigation-menus'] as $p) {
            $this->get("/admin/$p")->assertForbidden();
        }

        $this->login('super-admin');
        foreach (['pages', 'pages/create', 'services', 'testimonials', 'hero-slides', 'home-sections', 'statistics', 'navigation-menus', 'header-settings', 'site-settings'] as $p) {
            $this->get("/admin/$p")->assertOk();
        }
    }

    /** Production uses a serializing cache store; payloads must survive a cache round-trip. */
    public function test_api_payloads_survive_database_cache_store(): void
    {
        config(['cache.default' => 'database']);
        $this->app['cache']->forgetDriver('database');

        foreach (['site', 'home', 'pages', 'pages/about', 'services', 'testimonials', 'gallery/categories', 'videos'] as $endpoint) {
            $first = $this->getJson("/api/v1/$endpoint")->assertOk()->json();
            $second = $this->getJson("/api/v1/$endpoint")->assertOk()->json(); // served from cache
            $this->assertSame($first, $second);
            $this->assertStringNotContainsString('__PHP_Incomplete_Class', json_encode($second));
        }
        $this->assertSame('Home', $this->getJson('/api/v1/site')->json('data.menus.left.0.label'));
    }

    public function test_admin_can_switch_motion_effects_off_and_on(): void
    {
        $this->login();
        $this->get('/')->assertSee('js/fx.js', false);

        Livewire::test(SiteSettings::class)->assertFormSet(['site.effects' => '1'])
            ->fillForm(['site' => ['name' => 'Magic Frames', 'effects' => '0']])->call('save')->assertHasNoFormErrors();
        $this->assertSame('0', Setting::get('site.effects'));
        $this->get('/')->assertDontSee('js/fx.js', false)->assertDontSee('css/fx.css', false);

        Livewire::test(SiteSettings::class)->assertFormSet(['site.effects' => '0'])
            ->fillForm(['site' => ['name' => 'Magic Frames', 'effects' => '1']])->call('save');
        $this->get('/')->assertSee('js/fx.js', false);
    }
}
