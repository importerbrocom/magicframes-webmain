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
use Illuminate\Support\Facades\Http;
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

    /** A sample photograph: downloaded from picsum.photos, or generated locally if offline. */
    private function photo(string $seed, int $w, int $h, string $dir = 'demo'): string
    {
        $path = $dir.'/'.Str::slug($seed).'-'.Str::lower(Str::random(5)).'.jpg';
        try {
            $r = Http::timeout(25)->retry(1, 300, throw: false)->get("https://picsum.photos/seed/{$seed}/{$w}/{$h}.jpg");
            if ($r->successful() && str_starts_with((string) $r->header('Content-Type'), 'image/') && strlen($r->body()) > 2000) {
                Storage::disk('public')->put($path, $r->body());

                return $path;
            }
        } catch (\Throwable) {
            // offline / blocked: fall through to a generated placeholder
        }

        return $this->generated($path, $seed, $w, $h);
    }

    private function generated(string $path, string $seed, int $w, int $h): string
    {
        $hash = crc32($seed);
        $c1 = [100 + ($hash & 0x5F), 90 + (($hash >> 8) & 0x5F), 90 + (($hash >> 16) & 0x5F)];
        $c2 = [35, 30, 28];
        $im = imagecreatetruecolor($w, $h);
        for ($y = 0; $y < $h; $y++) {
            $t = $y / max(1, $h - 1);
            $c = imagecolorallocate($im, (int) ($c1[0] + ($c2[0] - $c1[0]) * $t), (int) ($c1[1] + ($c2[1] - $c1[1]) * $t), (int) ($c1[2] + ($c2[2] - $c1[2]) * $t));
            imageline($im, 0, $y, $w, $y, $c);
        }
        imagestring($im, 5, (int) ($w / 2) - 40, (int) ($h / 2) - 8, 'SAMPLE PHOTO', imagecolorallocate($im, 240, 230, 220));
        ob_start();
        imagejpeg($im, null, 85);
        Storage::disk('public')->put($path, ob_get_clean());
        imagedestroy($im);

        return $path;
    }

    /* ------------------------------------------------------------------ content */

    private function hero(): void
    {
        $this->say('Hero slider…');
        foreach ([
            ['Timeless Moments', 'Wedding & event photography', 'Sample slide: replace this text and picture in Admin > Homepage > Hero slides.', 'hero-one'],
            ['Love In Frames', 'Pre-wedding & couple shoots', null, 'hero-two'],
            ['Stories Told', 'Cinematic wedding films', null, 'hero-three'],
        ] as $i => [$heading, $sub, $desc, $seed]) {
            $this->track(HeroSlide::create([
                'heading' => $heading, 'subheading' => $sub, 'description' => $desc,
                'button_label' => $i === 0 ? 'Enquire Now' : null, 'button_url' => $i === 0 ? '/contact' : null,
                'image_path' => $this->photo($seed, 1900, 1100), 'sort_order' => $i,
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
                'image_path' => $this->photo('about-main', 900, 1150), 'image_secondary_path' => $this->photo('about-second', 700, 900),
                'button_label' => 'Learn More', 'button_url' => '/about',
            ],
            'quote' => ['label' => 'A thousand little moments', 'title' => '“Every frame tells a story”', 'image_path' => $this->photo('quote-bg', 1900, 900)],
            'services' => ['body' => 'Sample services shown for layout preview.'],
            'stats' => [], 'portfolio' => [], 'videos' => [], 'recent' => [],
            'testimonials' => ['image_path' => $this->photo('testimonial-bg', 1900, 900)],
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
        foreach (['about', 'services', 'portfolio', 'gallery', 'videos', 'blog', 'contact', 'shop'] as $slug) {
            if ($page = Page::where('slug', $slug)->first()) {
                $this->change($page, ['banner_image_path' => $this->photo("banner-$slug", 1900, 900)]);
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
            'image_path' => $this->photo('about-block', 900, 700), 'image_alt' => 'Sample photograph', 'image_right' => true, 'sort_order' => 2,
        ]));
        $this->track($about->sections()->create([
            'type' => 'cta', 'label' => 'Ready when you are', 'heading' => 'Let’s plan your day',
            'body' => '<p>Tell us about your celebration.</p>', 'button_label' => 'Get in Touch', 'button_url' => '/contact', 'sort_order' => 3,
        ]));
    }

    private function services(): void
    {
        $this->say('Services…');
        foreach ([
            ['Wedding Photography', 'fa-solid fa-camera-retro', 'Full-day coverage of your ceremony and celebrations.'],
            ['Pre-Wedding Shoots', 'fa-solid fa-heart', 'Relaxed, romantic sessions at the locations you love.'],
            ['Engagement Sessions', 'fa-solid fa-ring', 'Beautiful portraits to celebrate the announcement.'],
            ['Cinematic Films', 'fa-solid fa-film', 'Highlight films that capture the emotion of the day.'],
            ['Portrait Photography', 'fa-solid fa-user', 'Individual and family portraits with a natural feel.'],
            ['Event Coverage', 'fa-solid fa-champagne-glasses', 'Receptions, parties and milestone events.'],
        ] as $i => [$title, $icon, $short]) {
            $this->track(Service::create([
                'title' => $title, 'icon' => $icon, 'short_description' => $short.' (sample)', 'sort_order' => $i,
                'description' => '<p>Sample description for '.$title.'. Replace in Admin > Services.</p>',
            ]));
        }
    }

    private function testimonials(): void
    {
        $this->say('Testimonials…');
        foreach ([['Sample Client One', 'Bride'], ['Sample Client Two', 'Groom'], ['Sample Client Three', 'Family']] as $i => [$name, $role]) {
            $this->track(Testimonial::create([
                'client_name' => $name, 'client_role' => $role, 'rating' => 5, 'sort_order' => $i,
                'photo_path' => $this->photo("client-$i", 300, 300),
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
        $this->say('Gallery photos (this takes a minute)…');
        $plan = ['wedding-photography' => 10, 'pre-wedding-photography' => 8, 'engagement' => 6, 'couple-photography' => 6, 'portrait-photography' => 6, 'event-photography' => 4];
        $sizes = [[1200, 1600], [1600, 1000], [1200, 1200], [1000, 1500], [1600, 1100], [1100, 1400], [1500, 1000], [1200, 1700]];
        $n = 0;
        foreach ($plan as $slug => $count) {
            if (! $cat = GalleryCategory::where('slug', $slug)->first()) {
                continue;
            }
            $this->change($cat, ['description' => 'Sample gallery description. Edit it in Admin > Gallery > Categories.']);
            for ($i = 0; $i < $count; $i++, $n++) {
                [$w, $h] = $sizes[$n % count($sizes)];
                $this->track(GalleryImage::create([
                    'gallery_category_id' => $cat->id,
                    'title' => 'Sample photo '.($n + 1),
                    'description' => $n % 3 === 0 ? 'Sample caption: replace with your own text.' : null,
                    'alt_text' => 'Sample photograph',
                    'path' => $this->photo("gallery-$slug-$i", $w, $h, 'demo/gallery'),
                    'sort_order' => $i, 'is_featured' => $i < 2,
                ]));
            }
        }
    }

    private function videos(): void
    {
        $this->say('Videos…');
        // Open-licence films by the Blender Foundation (CC-BY), used only as sample embeds.
        foreach ([
            ['wedding-highlights', 'Sample film: Big Buck Bunny', 'aqz-KE-bpKQ', true],
            ['cinematic-wedding-films', 'Sample film: Sintel', 'eRsGyueVLvQ', true],
            ['pre-wedding-films', 'Sample film: Tears of Steel', 'R6MlUcmOul8', true],
            ['event-films', 'Sample film: Elephants Dream', 'TLkA0RELQ1g', false],
        ] as $i => [$cat, $title, $id, $featured]) {
            $this->track(Video::create([
                'video_category_id' => VideoCategory::where('slug', $cat)->value('id'),
                'title' => $title, 'description' => 'Sample video used to preview the player. Replace it in Admin > Videos with your own YouTube/Vimeo link or upload.',
                'source_type' => 'youtube', 'video_url' => "https://www.youtube.com/watch?v={$id}",
                'is_featured' => $featured, 'sort_order' => $i,
            ]));
        }
    }

    private function blog(): void
    {
        $this->say('Blog posts…');
        $posts = [
            ['Sample article: 5 tips for relaxed wedding portraits', 'Photography Tips'],
            ['Sample article: choosing the perfect pre-wedding location', 'Planning'],
            ['Sample article: how to prepare for your engagement shoot', 'Engagement'],
            ['Sample article: why a wedding film is worth it', 'Films'],
        ];
        foreach ($posts as $i => [$title, $category]) {
            $this->track(BlogPost::create([
                'title' => $title, 'category' => $category, 'author' => 'Sample Author',
                'excerpt' => 'Sample excerpt: a short summary of the article appears here on the blog list.',
                'content' => '<h2>A sample heading</h2><p>This is a sample article that shows how blog posts look on your website. Write real articles in Admin > Website > Blog posts.</p><p>You can add headings, lists, images and links with the editor, schedule posts for later, and add SEO details for each article.</p><ul><li>Sample point one</li><li>Sample point two</li><li>Sample point three</li></ul><p>Replace this text with your own advice, stories and real weddings.</p>',
                'cover_path' => $this->photo("blog-$i", 1400, 900, 'demo/blog'), 'cover_alt' => 'Sample cover photograph',
                'is_published' => true, 'published_at' => now()->subDays(3 + $i * 4),
            ]));
        }
    }

    private function settings(): void
    {
        $this->say('Contact details & social links (sample)…');
        $values = [
            'site.tagline' => 'Wedding & event photography studio',
            'contact.phone' => '+00 000 000 0000',
            'contact.email' => 'hello@example.com',
            'contact.address' => "Sample Street 1\nSample City 000000",
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
