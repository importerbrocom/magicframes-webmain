<?php

namespace App\Console\Commands;

use App\Models\GalleryImage;
use App\Services\ImageProcessor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Re-creates the web versions of every gallery photo in HD (bigger sizes, higher quality)
 * from the ORIGINAL uploads. Originals are never changed. Safe to run more than once.
 */
class ImagesHd extends Command
{
    protected $signature = 'images:hd';

    protected $description = 'Rebuild all gallery photo sizes in HD from the original uploads';

    public function handle(ImageProcessor $processor): int
    {
        $disk = Storage::disk('public');
        $done = $skipped = 0;
        $total = GalleryImage::count();
        $this->info("Rebuilding HD versions of {$total} gallery photos (originals are kept as they are)…");
        $bar = $this->output->createProgressBar($total);

        GalleryImage::query()->orderBy('id')->chunkById(25, function ($images) use ($processor, $disk, &$done, &$skipped, $bar) {
            foreach ($images as $image) {
                $bar->advance();
                if (! $image->path || ! $disk->exists($image->path)) {
                    $skipped++;
                    continue;
                }
                $old = (array) ($image->variants ?? []);
                $result = $processor->process($image->path);
                if (! $result['variants']) {
                    $skipped++;   // too large for PHP memory or not an image: the original is still used
                    continue;
                }
                foreach (array_diff($old, $result['variants']) as $stale) {
                    $disk->delete($stale);
                }
                $image->forceFill(['variants' => $result['variants'], 'width' => $result['width'], 'height' => $result['height']])->saveQuietly();
                $done++;
            }
        });
        $bar->finish();
        $this->newLine();

        // Old (lower quality) resized copies of banners, slides, covers… are rebuilt in HD on first view.
        foreach ($disk->directories('_cache') as $dir) {
            if ($dir !== '_cache/hd') {
                $disk->deleteDirectory($dir);
            }
        }
        \App\Services\ContentCache::bump();

        $this->info("Done: {$done} photos rebuilt in HD".($skipped ? ", {$skipped} kept as original" : '').'.');

        return self::SUCCESS;
    }
}
