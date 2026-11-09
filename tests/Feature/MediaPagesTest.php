<?php

namespace Tests\Feature;

use App\Filament\Resources\BlogPosts\Pages\CreateBlogPost;
use App\Filament\Resources\BlogPosts\Pages\EditBlogPost;
use App\Models\BlogPost;
use App\Models\GalleryCategory;
use App\Models\GalleryImage;
use App\Models\Page;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoCategory;
use Database\Seeders\RolesAndAdminSeeder;
use Database\Seeders\SiteContentSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class MediaPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->seed([RolesAndAdminSeeder::class, SiteContentSeeder::class]);
        Filament::setCurrentPanel('admin');
    }

    private function cat(string $name, int $images = 2, array $imageAttrs = []): GalleryCategory
    {
        $cat = GalleryCategory::create(['name' => $name, 'sort_order' => GalleryCategory::count()]);
        for ($i = 1; $i <= $images; $i++) {
            GalleryImage::create($imageAttrs + [
                'gallery_category_id' => $cat->id, 'title' => "$name $i", 'sort_order' => $i,
                'path' => UploadedFile::fake()->image("$name-$i.jpg", 1600, 1000)->store('gallery', 'public'),
            ]);
        }

        return $cat;
    }

    private function admin(string $role = 'super-admin'): User
    {
        $u = User::factory()->create(['is_active' => true]);
        $u->assignRole($role);
        $this->actingAs($u);

        return $u;
    }

    /* ---------------- portfolio & galleries ---------------- */

    public function test_portfolio_lists_only_categories_with_published_photos(): void
    {
        $this->get('/portfolio')->assertOk()->assertSee('Our galleries will appear here soon.');

        $this->cat('Weddings', 3);
        $this->cat('Empty Category', 0);
        $hidden = GalleryCategory::create(['name' => 'Hidden', 'is_published' => false]);
        GalleryImage::create(['gallery_category_id' => $hidden->id, 'path' => UploadedFile::fake()->image('h.jpg', 900, 600)->store('gallery', 'public')]);

        $html = $this->get('/portfolio')->assertOk()->getContent();
        $this->assertStringContainsString('href="/gallery/weddings"', $html);
        $this->assertStringContainsString('3 Photos', $html);
        $this->assertStringNotContainsString('empty-category', $html);
        $this->assertStringNotContainsString('/gallery/hidden', $html);
    }

    public function test_gallery_page_renders_lightbox_ready_masonry_with_filters(): void
    {
        $a = $this->cat('Weddings', 2);
        $this->cat('Portraits', 1);
        GalleryImage::create(['gallery_category_id' => $a->id, 'title' => 'Draft shot', 'is_published' => false,
            'path' => UploadedFile::fake()->image('d.jpg', 900, 600)->store('gallery', 'public')]);
        $a->images()->where('title', 'Weddings 1')->first()->update(['description' => 'A quiet moment', 'alt_text' => 'Couple laughing']);

        $html = $this->get('/gallery')->assertOk()->getContent();
        $this->assertStringContainsString('class="masonry"', $html);
        $this->assertSame(3, substr_count($html, 'data-lightbox'));
        $this->assertStringContainsString('data-caption="A quiet moment"', $html);
        $this->assertStringContainsString('alt="Couple laughing"', $html);
        $this->assertStringContainsString('aspect-ratio:1600/1000', $html);
        $this->assertStringContainsString('loading="lazy"', $html);
        $this->assertStringContainsString('srcset=', $html);
        $this->assertStringContainsString('/variants/', $html, 'responsive WebP variants are used');
        $this->assertStringNotContainsString('Draft shot', $html);
        // filters generated from the database
        $this->assertStringContainsString('data-filter="weddings"', $html);
        $this->assertStringContainsString('data-filter="portraits"', $html);
        $this->assertStringContainsString('data-layout="grid"', $html);
        $this->assertStringContainsString('js/media.js', $html);
    }

    public function test_gallery_category_filter_by_query_string_and_unknown_category(): void
    {
        $this->cat('Weddings', 2);
        $this->cat('Portraits', 1);
        $html = $this->get('/gallery?category=portraits')->assertOk()->getContent();
        $this->assertSame(1, substr_count($html, 'data-lightbox'));
        $this->assertStringContainsString('class="filter active" data-filter="portraits"', $html);
        $this->get('/gallery?category=nope')->assertNotFound();
    }

    public function test_gallery_load_more_state_and_api_paging(): void
    {
        $this->cat('Big', 30);
        $html = $this->get('/gallery')->getContent();
        $this->assertSame(24, substr_count($html, 'data-lightbox'));
        $this->assertStringContainsString('data-last="2"', $html);
        $this->assertStringNotContainsString('g-more" hidden', $html);

        $page2 = $this->getJson('/api/v1/gallery/images?page=2&per_page=24')->assertOk()->json();
        $this->assertCount(6, $page2['data']);
        $this->assertNotEmpty($page2['data'][0]['large_url']);
        $this->assertSame(2, $page2['meta']['current_page']);
    }

    public function test_individual_gallery_page_with_navigation_and_seo(): void
    {
        $w = $this->cat('Weddings', 2);
        $w->update(['description' => 'Our wedding stories.', 'meta_title' => 'Wedding Photography Gallery', 'meta_description' => 'Real weddings.']);
        $this->cat('Engagement', 1);
        $this->cat('Portraits', 1);

        $html = $this->get('/gallery/engagement')->assertOk()->getContent();
        $this->assertStringContainsString('Engagement', $html);
        $this->assertStringContainsString('class="gnav__prev" href="/gallery/weddings"', $html);
        $this->assertStringContainsString('class="gnav__next" href="/gallery/portraits"', $html);

        $html = $this->get('/gallery/weddings')->assertOk()->getContent();
        $this->assertStringContainsString('<title>Wedding Photography Gallery — Magic Frames</title>', $html);
        $this->assertStringContainsString('content="Real weddings."', $html);
        $this->assertStringContainsString('Our wedding stories.', $html);
        $this->assertStringContainsString('<link rel="canonical" href="'.url('/gallery/weddings').'" />', $html);
        $this->assertStringContainsString('property="og:image"', $html);
        $this->assertStringNotContainsString('gnav__prev', $html, 'first gallery has no previous');
        $this->assertSame(2, substr_count($html, 'data-lightbox'));
        $this->assertStringContainsString('page-banner__title">Weddings', $html);
    }

    public function test_empty_unpublished_and_missing_galleries_are_404(): void
    {
        GalleryCategory::create(['name' => 'Empty']);
        $hidden = $this->cat('Hidden', 1);
        $hidden->update(['is_published' => false]);
        foreach (['/gallery/empty', '/gallery/hidden', '/gallery/does-not-exist'] as $url) {
            $this->get($url)->assertNotFound()->assertSee('This page has wandered off');
        }
    }

    public function test_gallery_updates_when_admin_changes_content(): void
    {
        $cat = $this->cat('Live', 1);
        $this->assertStringContainsString('Live 1', $this->get('/gallery')->getContent());
        $cat->images()->first()->update(['title' => 'Renamed photo']);
        $html = $this->get('/gallery')->getContent();
        $this->assertStringContainsString('Renamed photo', $html);
        $cat->images()->first()->delete();
        $this->get('/gallery')->assertDontSee('Renamed photo')->assertSee('Our photographs will appear here soon.');
    }

    /* ---------------- videos ---------------- */

    public function test_video_page_lists_films_without_loading_them(): void
    {
        $cat = VideoCategory::create(['name' => 'Highlights']);
        Video::create(['video_category_id' => $cat->id, 'title' => 'Aarav "&" Mira', 'description' => 'Two days, one film.', 'source_type' => 'youtube', 'video_url' => 'https://youtu.be/dQw4w9WgXcQ']);
        Video::create(['title' => 'Uploaded teaser', 'source_type' => 'upload', 'video_path' => UploadedFile::fake()->create('t.mp4', 500, 'video/mp4')->store('videos', 'public'),
            'thumbnail_path' => UploadedFile::fake()->image('t.jpg', 800, 450)->store('videos/thumbnails', 'public')]);
        Video::create(['title' => 'Private draft', 'source_type' => 'youtube', 'video_url' => 'https://youtu.be/aaaaaaaaaaa', 'is_published' => false]);

        $html = $this->get('/videos')->assertOk()->getContent();
        $this->assertSame(2, substr_count($html, '<a class="vcard"'), 'only published films');
        $this->assertStringNotContainsString('Private draft', $html);
        $this->assertStringContainsString('data-embed="https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?rel=0&amp;modestbranding=1"', $html);
        $this->assertStringContainsString('Aarav &quot;&amp;&quot; Mira', $html, 'titles are escaped in attributes');
        $this->assertStringNotContainsString('<iframe', $html, 'no embeds loaded with the page');
        $this->assertStringNotContainsString('<video', $html, 'no video files loaded with the page');
        $this->assertStringContainsString('/storage/videos/', $html); // only as a data-file attribute
        $this->assertStringContainsString('hqdefault.jpg', $html, 'YouTube thumbnail');
        $this->assertStringContainsString('data-filter="highlights"', $html);
        $this->assertStringContainsString('Two days, one film.', $html);
    }

    public function test_video_filter_play_param_and_unknown_category(): void
    {
        $a = VideoCategory::create(['name' => 'Highlights']);
        $b = VideoCategory::create(['name' => 'Pre-Wedding Films']);
        Video::create(['video_category_id' => $a->id, 'title' => 'One', 'source_type' => 'youtube', 'video_url' => 'https://youtu.be/dQw4w9WgXcQ']);
        Video::create(['video_category_id' => $b->id, 'title' => 'Two', 'source_type' => 'youtube', 'video_url' => 'https://youtu.be/bbbbbbbbbbb']);

        $html = $this->get('/videos?category=pre-wedding-films')->assertOk()->getContent();
        $this->assertSame(1, substr_count($html, '<a class="vcard"'));
        $this->assertStringContainsString('data-play=""', $html);
        $this->assertStringContainsString('data-play="two"', $this->get('/videos?play=two')->getContent());
        $this->assertStringContainsString('data-play=""', $this->get('/videos?play=%22%3E%3Cscript%3E')->getContent(), 'invalid play values ignored');
        $this->get('/videos?category=nope')->assertNotFound();
        $this->getJson('/api/v1/videos/two')->assertOk()->assertJsonPath('data.embed_url', 'https://www.youtube-nocookie.com/embed/bbbbbbbbbbb?rel=0&modestbranding=1');
    }

    public function test_video_page_empty_state(): void
    {
        $this->get('/videos')->assertOk()->assertSee('Our films will appear here soon.');
    }

    /* ---------------- blog ---------------- */

    private function makePost(array $a = []): BlogPost
    {
        static $n = 0;
        $n++;

        return BlogPost::create($a + ['title' => "Post $n", 'content' => '<p>Body '.$n.'</p>', 'is_published' => true, 'published_at' => now()->subDays(20 - min($n, 19))]);
    }

    public function test_blog_lists_only_live_posts_with_category_search_and_pagination(): void
    {
        $this->get('/blog')->assertOk()->assertSee('Our first articles are on their way.');

        $this->makePost(['title' => 'Venue tips', 'category' => 'Planning', 'excerpt' => 'How to pick a venue']);
        $this->makePost(['title' => 'Draft idea', 'is_published' => false]);
        $this->makePost(['title' => 'Scheduled idea', 'published_at' => now()->addDays(3)]);

        $html = $this->get('/blog')->assertOk()->getContent();
        $this->assertStringContainsString('Venue tips', $html);
        $this->assertStringContainsString('How to pick a venue', $html);
        $this->assertStringNotContainsString('Draft idea', $html);
        $this->assertStringNotContainsString('Scheduled idea', $html);
        $this->assertStringContainsString('href="/blog/venue-tips"', $html);

        $this->makePost(['title' => 'Albums guide', 'category' => 'Albums']);
        $this->get('/blog?category=Planning')->assertSee('Venue tips')->assertDontSee('Albums guide');
        $this->get('/blog?q=albums')->assertSee('Albums guide')->assertDontSee('Venue tips');
        $this->get('/blog?q=zzzz')->assertSee('No articles match your search.');

        foreach (range(1, 10) as $i) {
            $this->makePost(['title' => "Bulk $i"]);
        }
        $page1 = $this->get('/blog')->getContent();
        $this->assertSame(9, substr_count($page1, '<article class="post"'), 'nine per page');
        $this->assertStringContainsString('rel="next"', $page1);
        $this->get('/blog?page=2')->assertOk()->assertSee('rel="prev"', false);
    }

    public function test_blog_post_page(): void
    {
        $post = $this->makePost([
            'title' => 'Real <b>wedding</b> story', 'author' => 'The Studio', 'category' => 'Real Weddings',
            'content' => '<h2>Day one</h2><p>'.str_repeat('word ', 450).'</p><script>alert("x")</script>',
            'cover_path' => UploadedFile::fake()->image('c.jpg', 1600, 900)->store('blog', 'public'), 'cover_alt' => 'Couple by the lake',
        ]);
        $post->seo()->create(['meta_title' => 'A lovely story', 'meta_description' => 'Short summary here.']);

        $html = $this->get('/blog/'.$post->slug)->assertOk()->getContent();
        $this->assertStringContainsString('<title>A lovely story — Magic Frames</title>', $html);
        $this->assertStringContainsString('content="Short summary here."', $html);
        $this->assertStringContainsString('Day one', $html);
        $this->assertStringNotContainsString('alert("x")', $html);
        $this->assertStringNotContainsString('<b>wedding</b>', $html, 'title is escaped');
        $this->assertStringContainsString('3 min read', $html);
        $this->assertStringContainsString('The Studio', $html);
        $this->assertStringContainsString('property="og:image"', $html);

        preg_match('~<script type="application/ld\+json">(.*?)</script>~s', $html, $m);
        $ld = json_decode($m[1], true);
        $this->assertSame('BlogPosting', $ld['@type']);
        $this->assertSame('The Studio', $ld['author']['name']);
    }

    public function test_draft_scheduled_and_missing_posts_are_404_and_scheduling_goes_live(): void
    {
        $draft = $this->makePost(['title' => 'Hidden draft', 'is_published' => false]);
        $sched = $this->makePost(['title' => 'Coming soon', 'published_at' => now()->addHour()]);
        $this->get('/blog/'.$draft->slug)->assertNotFound();
        $this->get('/blog/'.$sched->slug)->assertNotFound();
        $this->get('/blog/nope')->assertNotFound();
        $this->getJson('/api/v1/blog/'.$draft->slug)->assertNotFound();

        $this->travel(2)->hours();
        \Illuminate\Support\Facades\Cache::flush();
        $this->get('/blog/'.$sched->slug)->assertOk();
    }

    public function test_blog_api_and_sitemap(): void
    {
        $p = $this->makePost(['title' => 'Api post', 'category' => 'Tips', 'excerpt' => 'Excerpt text']);
        $this->getJson('/api/v1/blog')->assertOk()->assertJsonPath('data.0.title', 'Api post')->assertJsonMissingPath('data.0.content');
        $this->getJson('/api/v1/blog/'.$p->slug)->assertOk()->assertJsonPath('data.content', $p->content)->assertJsonPath('data.seo.title', 'Api post — Magic Frames');
        $this->getJson('/api/v1/blog-categories')->assertJsonPath('data.0', 'Tips');
        $this->assertStringContainsString(url('/blog/api-post'), $this->get('/sitemap.xml')->getContent());

        $p->update(['title' => 'Api post edited']);
        $this->getJson('/api/v1/blog')->assertJsonPath('data.0.title', 'Api post edited');
        $p->delete();
        $this->getJson('/api/v1/blog')->assertJsonCount(0, 'data');
    }

    public function test_admin_can_write_schedule_and_manage_posts(): void
    {
        $this->admin();
        Livewire::test(CreateBlogPost::class)
            ->fillForm([
                'title' => 'Our first article', 'category' => 'News', 'author' => 'Studio',
                'content' => '<p>Hello world</p>', 'is_published' => true,
                'cover_path' => [UploadedFile::fake()->image('cover.jpg', 1200, 800)],
                'seo' => ['meta_title' => 'First!', 'meta_description' => 'Intro'],
            ])->call('create')->assertHasNoFormErrors();

        $post = BlogPost::firstOrFail();
        $this->assertSame('our-first-article', $post->slug);
        $this->assertNotNull($post->published_at, 'publishing without a date means now');
        Storage::disk('public')->assertExists($post->cover_path);
        $this->get('/blog/our-first-article')->assertOk()->assertSee('Hello world', false);

        // the form refuses a duplicate address; the model still guarantees uniqueness when the slug is empty
        Livewire::test(CreateBlogPost::class)->fillForm(['title' => 'Our first article', 'content' => '<p>x</p>'])->call('create')->assertHasFormErrors(['slug']);
        $dupe = BlogPost::create(['title' => 'Our first article', 'content' => '<p>x</p>']);
        $this->assertSame('our-first-article-2', $dupe->slug);
        $dupe->delete();
        Livewire::test(CreateBlogPost::class)->fillForm(['title' => '', 'content' => ''])->call('create')->assertHasFormErrors(['title', 'content']);

        // unpublish + schedule
        Livewire::test(EditBlogPost::class, ['record' => $post->getRouteKey()])
            ->fillForm(['is_published' => true, 'published_at' => now()->addDays(2)])->call('save')->assertHasNoFormErrors();
        $this->get('/blog/our-first-article')->assertNotFound();

        // delete removes the cover file
        $cover = $post->fresh()->cover_path;
        $post->delete();
        Storage::disk('public')->assertMissing($cover);
    }

    public function test_blog_admin_permissions(): void
    {
        $this->admin('editor');
        $this->get('/admin/blog-posts')->assertOk();
        $this->admin('enquiry-handler');
        $this->get('/admin/blog-posts')->assertForbidden();
    }

    /* ---------------- whole-site link check ---------------- */

    public function test_all_internal_links_resolve_once_content_exists(): void
    {
        $cat = $this->cat('Weddings', 2);
        VideoCategory::create(['name' => 'Highlights'])->videos()->create(['title' => 'Film', 'slug' => 'film', 'source_type' => 'youtube', 'video_url' => 'https://youtu.be/dQw4w9WgXcQ', 'is_featured' => true]);
        $this->makePost(['title' => 'Journal entry']);
        Page::where('slug', 'privacy-policy')->first()->update(['is_published' => true]);
        $cat->images()->first()->update(['is_featured' => true]);
        \App\Models\HomeSection::whereIn('key', ['portfolio', 'videos', 'recent'])->get()->each->update(['is_visible' => true]);

        $seen = [];
        $queue = ['/'];
        $broken = [];
        while ($queue) {
            $path = array_shift($queue);
            if (isset($seen[$path])) {
                continue;
            }
            $seen[$path] = true;
            $res = $this->get($path);
            if ($res->getStatusCode() !== 200) {
                $broken[] = "$path ({$res->getStatusCode()})";
                continue;
            }
            preg_match_all('~href="(/[^"#]*)"~', $res->getContent(), $m);
            foreach ($m[1] as $href) {
                $href = html_entity_decode($href);
                if (preg_match('~^/(storage|css|js|images|admin|api)~', $href) || str_contains($href, '?')) {
                    continue;
                }
                $queue[] = $href;
            }
        }
        $this->assertSame([], $broken, 'broken links: '.implode(', ', $broken));
        foreach (['/gallery', '/gallery/weddings', '/videos', '/blog', '/blog/journal-entry', '/portfolio', '/contact', '/about', '/services', '/privacy-policy'] as $expected) {
            $this->assertArrayHasKey($expected, $seen, "crawler should reach $expected");
        }
    }

    public function test_media_script_is_valid_javascript(): void
    {
        $out = [];
        exec('node --check '.escapeshellarg(public_path('js/media.js')).' 2>&1', $out, $code);
        $this->assertSame(0, $code, implode("\n", $out));
    }
}
