<?php

namespace Database\Seeders;

use App\Models\GalleryCategory;
use App\Models\VideoCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds the category *structure* only (names suggested in the project brief).
 * No images, videos or business content is invented — the admin uploads those.
 * New categories are created unpublished-safe: they simply stay empty until used.
 */
class GalleryStructureSeeder extends Seeder
{
    public function run(): void
    {
        $gallery = [
            'Wedding Photography', 'Pre-Wedding Photography', 'Engagement', 'Couple Photography',
            'Portrait Photography', 'Event Photography', 'Candid Photography', 'Traditional Photography',
        ];
        foreach ($gallery as $i => $name) {
            GalleryCategory::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name, 'sort_order' => $i, 'is_published' => true]);
        }

        $video = ['Wedding Highlights', 'Pre-Wedding Films', 'Cinematic Wedding Films', 'Event Films'];
        foreach ($video as $i => $name) {
            VideoCategory::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name, 'sort_order' => $i, 'is_published' => true]);
        }
    }
}
