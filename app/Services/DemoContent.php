<?php

namespace App\Services;

use App\Models\BlogPost;
use App\Models\GalleryCategory;
use App\Models\GalleryImage;
use App\Models\HeroSlide;
use App\Models\HomeSection;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Statistic;
use App\Models\Testimonial;
use App\Models\Video;
use App\Models\VideoCategory;
use Closure;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Fills the site with clearly-labelled SAMPLE content so the studio can preview every feature,
 * and removes exactly that content again (nothing the studio typed is touched).
 * Everything created or changed is recorded in storage/app/demo-manifest.json.
 */
class DemoContent
{
    /** @var array<string,mixed> */
    private array $m = ['created' => [], 'updated' => [], 'settings' => []];

    private ?Closure $say = null;

    public static function manifestPath(): string
    {
        return storage_path('app/demo-manifest.json');
    }

    public function installed(): bool
    {
        return is_file(self::manifestPath());
    }

    /** @return array<string,int> */
    public function install(?Closure $say = null): array
    {
        $this->say = $say;
        File::ensureDirectoryExists(dirname(self::manifestPath()));
        $this->m = ['created' => [], 'updated' => [], 'settings' => []];

        try {
            $this->hero();
            $this->homeSections();
            $this->pageBanners();
            $this->aboutBlocks();
            $this->services();
            $this->testimonials();
            $this->statistics();
            $this->gallery();
            $this->videos();
            $this->blog();
            $this->settings();
        } finally {
            file_put_contents(self::manifestPath(), json_encode($this->m, JSON_PRETTY_PRINT));
        }

        return collect($this->m['created'])->map(fn ($ids) => count($ids))->all();
    }

    /** @return array<string,int> */
    public function remove(?Closure $say = null): array
    {
        $this->say = $say;
        if (! $this->installed()) {
            return [];
        }
        $m = json_decode(file_get_contents(self::manifestPath()), true);
        $removed = [];

        foreach ($m['created'] as $class => $ids) {
            foreach ($class::whereIn('id', $ids)->get() as $model) {
                $model->delete(); // model hooks also delete the files
            }
            $removed[class_basename($class)] = count($ids);
        }
        foreach ($m['updated'] as $class => $rows) {
            foreach ($rows as $id => $original) {
                if ($model = $class::find($id)) {
                    $model->forceFill($original)->save(); // restoring a file column deletes the sample file
                }
            }
        }
        Setting::setMany($m['settings']);

        File::delete(self::manifestPath());
        Storage::disk('public')->deleteDirectory('demo');

        return $removed;
    }

    /* ------------------------------------------------------------------ helpers */

    private function say(string $line): void
    {
        if ($this->say) {
            ($this->say)($line);
        }
    }

    private function track($model)
    {
        $this->m['created'][get_class($model)][] = $model->getKey();

        return $model;
    }

    /** Remember the original values of columns we are about to change. */
    private function snapshot($model, array $columns): void
    {
        $class = get_class($model);
        if (! isset($this->m['updated'][$class][$model->getKey()])) {
            $this->m['updated'][$class][$model->getKey()] = $model->only($columns);
        }
    }

    private function change($model, array $values): void
    {
        $this->snapshot($model, array_keys($values));
        $model->update($values);
    }

    /** @var array<string,array<string,string>>|null */
    private ?array $credits = null;

    /** Credits (author, licence, caption, alt…) for the bundled Kerala sample photographs. */
    private function credit(string $key): array
    {
        $this->credits ??= json_decode((string) file_get_contents(database_path('demo-images/credits.json')), true) ?: [];

        return $this->credits[$key] ?? throw new \RuntimeException("Unknown demo image: {$key}");
    }

    /** Copy a bundled HD sample photograph into public storage and return its path (works offline). */
    private function photo(string $key, string $dir = 'demo'): string
    {
        $this->credit($key);
        $source = database_path("demo-images/{$key}.jpg");
        if (! is_file($source)) {
            throw new \RuntimeException("Missing demo image file: {$source}");
        }
        $path = $dir.'/'.$key.'-'.Str::lower(Str::random(4)).'.jpg';
        Storage::disk('public')->put($path, file_get_contents($source));

        return $path;
    }

    private function creditLine(string $key): string
    {
        $c = $this->credit($key);

        return 'Sample photo: '.$c['author'].' · '.$c['license'].' · Wikimedia Commons';
    }

    /* ------------------------------------------------------------------ content */

    private function hero(): void
    {
        $this->say('Hero slider…');
        foreach ([
            ['Timeless Moments', 'Kerala wedding & engagement photography', 'Sample slide: replace this text and picture in Admin > Homepage > Hero slides.', 'prewed-banana-grove'],
            ['Love In Frames', 'Pre-wedding & couple stories', null, 'wedding-garland'],
            ['Stories Told', 'Traditions, rituals & celebrations', null, 'wedding-ritual'],
        ] as $i => [$heading, $sub, $desc, $key]) {
            $this->track(HeroSlide::create([
                'heading' => $heading, 'subheading' => $sub, 'description' => $desc,
                'button_label' => $i === 0 ? 'Enquire Now' : null, 'button_url' => $i === 0 ? '/contact' : null,
                'image_path' => $this->photo($key, 'demo/hero'), 'sort_order' => $i,
            ]));
        }
    }

    private function homeSections(): void
    {
        $this->say('Home page blocks…');
        $cols = ['label', 'title', 'body', 'body_secondary', 'image_path', 'image_secondary_path', 'button_label', 'button_url', 'is_visible'];
        $values = [
            'hero' => [],
            'about' => [
                'body' => "Sample text: this is where the studio introduces itself. Describe your style, your experience and what makes your photography special.\n\nReplace this with your own story in Admin > Homepage > Home sections.",
                'body_secondary' => 'A second short paragraph can go here, for example how you work with couples and families.',
                'image_path' => $this->photo('prewed-umbrella', 'demo/home'), 'image_secondary_path' => $this->photo('wedding-lamps', 'demo/home'),
                'button_label' => 'Learn More', 'button_url' => '/about',
            ],
            'quote' => ['label' => 'A thousand little moments', 'title' => '“Every frame tells a story”', 'image_path' => $this->photo('wedding-thali', 'demo/home')],
            'services' => ['body' => 'Sample services shown for layout preview.'],
            'stats' => [], 'portfolio' => [], 'videos' => [], 'recent' => [],
            'testimonials' => ['image_path' => $this->photo('wedding-ritual', 'demo/home')],
            'social' => ['body' => 'Follow our latest work.'],
            'contact' => [],
        ];
        foreach ($values as $key => $changes) {
            if ($section = HomeSection::where('key', $key)->first()) {
                $this->snapshot($section, $cols);
                $section->update($changes + ['is_visible' => true]);
            }
        }
    }

    private function pageBanners(): void
    {
        $this->say('Page banners…');
        $map = [
            'about' => 'wedding-ritual', 'services' => 'event-silk-saree', 'portfolio' => 'wedding-family', 'gallery' => 'wedding-garland',
            'videos' => 'engage-lamp-setup', 'blog' => 'engage-offerings', 'contact' => 'wedding-nirapara', 'shop' => 'event-stage-drapes',
        ];
        foreach ($map as $slug => $key) {
            if ($page = Page::where('slug', $slug)->first()) {
                $this->change($page, ['banner_image_path' => $this->photo($key, 'demo/banners')]);
            }
        }
    }

    private function aboutBlocks(): void
    {
        $this->say('About page content…');
        if (! $about = Page::where('slug', 'about')->first()) {
            return;
        }
        $this->track($about->sections()->create([
            'type' => 'text', 'label' => 'Our Story', 'heading' => 'Sample: who we are',
            'body' => '<p>This is a sample content block. Write about your studio, your team and how you approach every celebration.</p><p>Blocks can contain text, pictures, videos or a call-to-action and can be re-ordered in the admin.</p>',
            'sort_order' => 1,
        ]));
        $this->track($about->sections()->create([
            'type' => 'image_text', 'label' => 'Our Approach', 'heading' => 'Sample: image with text',
            'body' => '<p>An image next to your text works well for introducing a service or a person.</p>',
            'image_path' => $this->photo('wedding-nirapara', 'demo/pages'), 'image_alt' => $this->credit('wedding-nirapara')['alt'], 'image_right' => true, 'sort_order' => 2,
        ]));
        $this->track($about->sections()->create([
            'type' => 'cta', 'label' => 'Ready when you are', 'heading' => 'Let’s plan your day',
            'body' => '<p>Tell us about your celebration.</p>', 'button_label' => 'Get in Touch', 'button_url' => '/contact', 'sort_order' => 3,
        ]));
        $this->track($about->sections()->create([
            'type' => 'text', 'label' => 'Preview only', 'heading' => 'Sample photo credits',
            'body' => $this->creditsHtml(), 'sort_order' => 4,
        ]));
    }

    /** Visible attribution for every sample photograph (required by the Creative Commons licences). */
    private function creditsHtml(): string
    {
        $items = collect($this->allCredits())->map(fn ($c) => sprintf(
            '<li>%s — %s, %s (<a href="%s">source</a>)</li>',
            e($c['caption']), e($c['author']), e($c['license']), e($c['source'])
        ))->implode('');

        return '<p>These sample photographs are shown only to preview the website. They come from Wikimedia Commons under free licences and will be removed with <strong>php artisan demo:remove</strong>. Replace them with the studio’s own work before launch.</p><ul>'.$items.'</ul>';
    }

    /** @return array<string,array<string,string>> */
    private function allCredits(): array
    {
        $this->credit('wedding-ritual'); // loads credits.json

        return $this->credits;
    }

    private function services(): void
    {
        $this->say('Services…');
        $photos = [
            'Wedding Photography' => ['wedding-ritual', 'wedding-garland', 'wedding-lamps', 'wedding-nirapara', 'wedding-family', 'wedding-petals', 'wedding-thali', 'wedding-thali-christian'],
            'Pre-Wedding Shoots' => ['prewed-banana-grove', 'prewed-pink-canopy', 'prewed-umbrella'],
            'Engagement Sessions' => ['engage-rings-couple', 'engage-lamp-setup', 'engage-offerings'],
            'Cinematic Films' => ['couple-happy', 'couple-garlands', 'couple-smiling'],
            'Portrait Photography' => ['portrait-veil', 'portrait-silver', 'portrait-orange-veil'],
            'Event Coverage' => ['event-stage-flowers', 'event-stage-drapes', 'event-silk-saree'],
        ];
        $wedding = [
            'description' => '<p>Sample text: this is what a full service page looks like. Your wedding is told from the first ritual to the last farewell: the haldi and mehendi mornings, the muhurtham, the garlands and the lamp, the family portraits and the quiet moments in between.</p><h3>How we work</h3><p>We meet before the day to plan the ceremonies and the family groups, then shoot unobtrusively so everyone stays relaxed. Your edited gallery is delivered online and in a printed album.</p><p>Replace all of this in Admin &gt; Website &gt; Services &gt; Wedding Photography.</p>',
            'highlights' => ['Sample: two photographers for the full day', 'Sample: all rituals and family portraits covered', 'Sample: edited online gallery within 30 days', 'Sample: hand-bound printed album', 'Sample: highlight film of the day'],
            'faqs' => [
                ['question' => 'Sample question: how early should we book?', 'answer' => "Sample answer. Wedding dates fill up quickly, so please enquire as early as you can.\n\nReplace this in the Extra tab of the service."],
                ['question' => 'Sample question: do you travel for destination weddings?', 'answer' => 'Sample answer. Yes, travel across Kerala and beyond can be arranged.'],
                ['question' => 'Sample question: when will we receive our photos?', 'answer' => 'Sample answer. A first preview within a week, the full edited gallery in about 30 days.'],
                ['question' => 'Sample question: can we choose the photos for the album?', 'answer' => 'Sample answer. Yes, you choose your favourites and we design the album with you.'],
            ],
            'videos' => [
                ['title' => 'Sample wedding highlights (placeholder video)', 'source' => 'link', 'url' => 'https://www.youtube.com/watch?v=aqz-KE-bpKQ', 'thumb' => 'wedding-garland', 'description' => 'Placeholder: the player plays a free open film. Replace it with your own wedding film in the service Videos tab.'],
                ['title' => 'Sample ceremony film (placeholder video)', 'source' => 'link', 'url' => 'https://www.youtube.com/watch?v=eRsGyueVLvQ', 'thumb' => 'wedding-ritual', 'description' => 'Placeholder video.'],
                ['title' => 'Sample family moments (placeholder video)', 'source' => 'link', 'url' => 'https://www.youtube.com/watch?v=R6MlUcmOul8', 'thumb' => 'wedding-family', 'description' => 'Placeholder video.'],
            ],
        ];
        foreach ([
            ['Wedding Photography', 'fa-solid fa-camera-retro', 'Full-day coverage of your ceremony and celebrations.'],
            ['Pre-Wedding Shoots', 'fa-solid fa-heart', 'Relaxed, romantic sessions at the locations you love.'],
            ['Engagement Sessions', 'fa-solid fa-ring', 'Beautiful portraits to celebrate the announcement.'],
            ['Cinematic Films', 'fa-solid fa-film', 'Highlight films that capture the emotion of the day.'],
            ['Portrait Photography', 'fa-solid fa-user', 'Individual and family portraits with a natural feel.'],
            ['Event Coverage', 'fa-solid fa-champagne-glasses', 'Receptions, parties and milestone events.'],
        ] as $i => [$title, $icon, $short]) {
            $extra = $title === 'Wedding Photography' ? $wedding : [];
            $videos = array_map(fn ($v) => [
                'title' => $v['title'], 'source' => 'link', 'url' => $v['url'], 'description' => $v['description'],
                'thumbnail' => $this->photo($v['thumb'], 'demo/services'),
            ], $extra['videos'] ?? []);
            $this->track(Service::create([
                'title' => $title, 'icon' => $icon, 'short_description' => $short.' (sample)', 'sort_order' => $i,
                'description' => $extra['description'] ?? '<p>Sample text for the '.$title.' page. Describe what you offer, how the day works and what makes your style special.</p><p>Replace everything on this page in Admin &gt; Website &gt; Services &gt; Edit.</p>',
                'highlights' => $extra['highlights'] ?? ['Sample point: replace with what is included', 'Sample point: delivery time and format', 'Sample point: number of photographers'],
                'faqs' => $extra['faqs'] ?? [['question' => 'Sample question: how do we book?', 'answer' => 'Sample answer. Replace this in the Extra tab of the service.']],
                'gallery' => array_map(fn ($key) => ['image' => $this->photo($key, 'demo/services'), 'title' => $this->credit($key)['caption'] ?? $title, 'alt' => $this->credit($key)['alt'] ?? $title.' sample photo'], $photos[$title]),
                'videos' => $videos,
            ]));
        }
    }

    private function testimonials(): void
    {
        $this->say('Testimonials…');
        // No photographs: real people's pictures are never attached to made-up sample reviews.
        foreach ([['Sample Client One', 'Bride'], ['Sample Client Two', 'Groom'], ['Sample Client Three', 'Family']] as $i => [$name, $role]) {
            $this->track(Testimonial::create([
                'client_name' => $name, 'client_role' => $role, 'rating' => 5, 'sort_order' => $i,
                'body' => 'This is a sample testimonial shown only to preview the layout. Replace it with a genuine review from a real client.',
            ]));
        }
    }

    private function statistics(): void
    {
        $this->say('Statistics…');
        foreach ([['Sample: weddings', 120, '+'], ['Sample: happy couples', 250, '+'], ['Sample: years', 8, ''], ['Sample: films', 60, '+']] as $i => [$label, $value, $suffix]) {
            $this->track(Statistic::create(['label' => $label, 'value' => $value, 'suffix' => $suffix, 'sort_order' => $i]));
        }
    }

    private function gallery(): void
    {
        $this->say('Gallery photos (Kerala weddings & engagements)…');
        $plan = [
            'wedding-photography' => ['Sample gallery: Kerala Hindu, Christian and Muslim wedding moments.', ['wedding-ritual', 'wedding-garland', 'wedding-nirapara', 'wedding-family', 'wedding-petals', 'wedding-thali-christian', 'wedding-lamps', 'wedding-thali']],
            'pre-wedding-photography' => ['Sample gallery: relaxed couple portraits before the big day.', ['prewed-banana-grove', 'prewed-umbrella', 'prewed-pink-canopy']],
            'engagement' => ['Sample gallery: ring exchanges and ceremony details.', ['engage-rings-hands', 'engage-rings-couple', 'engage-rings-book', 'engage-lamp-setup', 'engage-offerings']],
            'couple-photography' => ['Sample gallery: smiles and togetherness.', ['couple-smiling', 'couple-garlands', 'couple-happy']],
            'portrait-photography' => ['Sample gallery: bridal portraits and jewellery.', ['portrait-bw-bride', 'portrait-jewellery', 'portrait-veil', 'portrait-silver', 'portrait-orange-veil']],
            'event-photography' => ['Sample gallery: stages, decor and wedding-day details.', ['event-stage-flowers', 'event-stage-drapes', 'event-silk-saree']],
        ];
        foreach ($plan as $slug => [$description, $keys]) {
            if (! $cat = GalleryCategory::where('slug', $slug)->first()) {
                continue;
            }
            $this->change($cat, ['description' => $description]);
            foreach ($keys as $i => $key) {
                $c = $this->credit($key);
                $this->track(GalleryImage::create([
                    'gallery_category_id' => $cat->id,
                    'title' => $c['caption'],
                    'description' => $this->creditLine($key),
                    'alt_text' => $c['alt'],
                    'path' => $this->photo($key, 'demo/gallery'),
                    'sort_order' => $i, 'is_featured' => $i === 0,
                ]));
            }
        }
    }

    private function videos(): void
    {
        $this->say('Videos…');
        // Placeholder films: the open-licence Blender Foundation shorts (CC-BY) so the player can be tested.
        // The thumbnails are wedding photographs so the page previews the final look.
        foreach ([
            ['wedding-highlights', 'Sample wedding highlights (placeholder video)', 'aqz-KE-bpKQ', 'wedding-garland', true],
            ['cinematic-wedding-films', 'Sample cinematic film (placeholder video)', 'eRsGyueVLvQ', 'prewed-banana-grove', true],
            ['pre-wedding-films', 'Sample pre-wedding film (placeholder video)', 'R6MlUcmOul8', 'prewed-umbrella', true],
            ['event-films', 'Sample event film (placeholder video)', 'TLkA0RELQ1g', 'event-stage-flowers', false],
        ] as $i => [$cat, $title, $id, $thumb, $featured]) {
            $this->track(Video::create([
                'video_category_id' => VideoCategory::where('slug', $cat)->value('id'),
                'title' => $title,
                'description' => 'Placeholder: the player plays a free open film. Replace it in Admin > Videos with your own YouTube/Vimeo link or upload.',
                'source_type' => 'youtube', 'video_url' => "https://www.youtube.com/watch?v={$id}",
                'thumbnail_path' => $this->photo($thumb, 'demo/videos'),
                'is_featured' => $featured, 'sort_order' => $i,
            ]));
        }
    }

    private function blog(): void
    {
        $this->say('Blog posts…');
        $posts = [
            ['Sample article: 5 tips for relaxed wedding portraits', 'Photography Tips', 'wedding-nirapara'],
            ['Sample article: choosing the perfect pre-wedding location', 'Planning', 'prewed-banana-grove'],
            ['Sample article: how to prepare for your engagement shoot', 'Engagement', 'engage-rings-book'],
            ['Sample article: why a wedding film is worth it', 'Films', 'wedding-garland'],
        ];
        foreach ($posts as $i => [$title, $category, $cover]) {
            $this->track(BlogPost::create([
                'title' => $title, 'category' => $category, 'author' => 'Sample Author',
                'excerpt' => 'Sample excerpt: a short summary of the article appears here on the blog list.',
                'content' => '<h2>A sample heading</h2><p>This is a sample article that shows how blog posts look on your website. Write real articles in Admin > Website > Blog posts.</p><p>You can add headings, lists, images and links with the editor, schedule posts for later, and add SEO details for each article.</p><ul><li>Sample point one</li><li>Sample point two</li><li>Sample point three</li></ul><p>Replace this text with your own advice, stories and real weddings.</p>',
                'cover_path' => $this->photo($cover, 'demo/blog'), 'cover_alt' => $this->credit($cover)['alt'],
                'is_published' => true, 'published_at' => now()->subDays(3 + $i * 4),
            ]));
        }
    }

    private function settings(): void
    {
        $this->say('Contact details & social links (sample)…');
        $values = [
            'site.tagline' => 'Kerala wedding & engagement photography',
            'contact.phone' => '+00 000 000 0000',
            'contact.email' => 'hello@example.com',
            'contact.address' => "Sample Street 1\nKochi, Kerala 000000 (sample)",
            'contact.hours' => 'Mon–Sat · 10:00 – 18:00 (sample)',
            'social.instagram' => 'https://www.instagram.com/',
            'social.facebook' => 'https://www.facebook.com/',
            'social.youtube' => 'https://www.youtube.com/',
        ];
        foreach ($values as $key => $value) {
            $this->m['settings'][$key] = Setting::get($key);
        }
        Setting::setMany($values);
    }
}
