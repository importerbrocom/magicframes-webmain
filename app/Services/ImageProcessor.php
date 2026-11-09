<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Generates responsive WebP variants for an uploaded image using GD.
 * The original upload is kept untouched (lightbox "full" view); variants are
 * used for grid thumbnails and srcset delivery.
 */
class ImageProcessor
{
    public const WIDTHS = [480, 960, 1600];

    public function __construct(private string $disk = 'public') {}

    /**
     * @return array{width:int|null,height:int|null,variants:array<int,string>}
     */
    public function process(string $path): array
    {
        $disk = Storage::disk($this->disk);
        $empty = ['width' => null, 'height' => null, 'variants' => []];

        if (! $disk->exists($path) || ! function_exists('imagecreatefromstring') || ! function_exists('imagewebp')) {
            return $empty;
        }

        $raw = $disk->get($path);
        $size = @getimagesizefromstring($raw);
        if (! $size) {
            return $empty;
        }
        // Decoding needs ~4 bytes/pixel; camera originals can exceed PHP's memory_limit.
        if (! self::allowMemoryFor($size[0], $size[1])) {
            Log::warning("Image too large to resize safely, original kept: {$path} ({$size[0]}x{$size[1]})");

            return ['width' => $size[0], 'height' => $size[1], 'variants' => []];
        }
        $src = @imagecreatefromstring($raw);
        if (! $src) {
            return $empty;
        }

        $src = $this->applyExifOrientation($src, $raw);
        $width = imagesx($src);
        $height = imagesy($src);

        $dir = trim(dirname($path), '/.');
        $name = pathinfo($path, PATHINFO_FILENAME);
        $variants = [];

        foreach (self::WIDTHS as $target) {
            if ($width < $target && $target !== self::WIDTHS[0]) {
                continue; // never upscale (but always keep the smallest size)
            }
            $w = min($target, $width);
            $h = (int) round($height * ($w / $width));
            $dst = imagecreatetruecolor($w, $h);
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $w, $h, $width, $height);

            ob_start();
            imagewebp($dst, null, 82);
            $data = ob_get_clean();
            imagedestroy($dst);

            $variantPath = ($dir !== '' ? $dir.'/' : '')."variants/{$name}-{$target}.webp";
            $disk->put($variantPath, $data);
            $variants[$target] = $variantPath;
        }

        imagedestroy($src);

        return ['width' => $width, 'height' => $height, 'variants' => $variants];
    }

    /**
     * Make sure PHP can hold a decoded width×height image. Raises memory_limit when possible
     * (up to media.max_processing_memory_mb); returns false if it would still not fit.
     */
    public static function allowMemoryFor(int $width, int $height): bool
    {
        $needed = (int) ($width * $height * 4 * 2.5) + 48 * 1048576;
        $limit = self::memoryLimitBytes();
        if ($limit < 0) {
            return true; // unlimited
        }
        $used = memory_get_usage(true);
        if ($used + $needed <= $limit) {
            return true;
        }
        $ceiling = (int) config('media.max_processing_memory_mb', 1024) * 1048576;
        if ($used + $needed > $ceiling) {
            return false;
        }

        return @ini_set('memory_limit', (string) ($used + $needed)) !== false;
    }

    private static function memoryLimitBytes(): int
    {
        $raw = trim((string) ini_get('memory_limit'));
        if ($raw === '' || $raw === '-1') {
            return -1;
        }
        $n = (int) $raw;

        return match (strtolower(substr($raw, -1))) {
            'g' => $n * 1073741824,
            'm' => $n * 1048576,
            'k' => $n * 1024,
            default => $n,
        };
    }

    /** @param array<int,string>|null $variants */
    public function delete(?string $path, ?array $variants = null): void
    {
        $disk = Storage::disk($this->disk);
        if ($path) {
            $disk->delete($path);
        }
        foreach ($variants ?? [] as $variant) {
            $disk->delete($variant);
        }
    }

    private function applyExifOrientation(\GdImage $img, string $raw): \GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $img;
        }
        $exif = @exif_read_data('data://image/jpeg;base64,'.base64_encode($raw));
        $orientation = $exif['Orientation'] ?? 1;
        $rotated = match ($orientation) {
            3 => imagerotate($img, 180, 0),
            6 => imagerotate($img, -90, 0),
            8 => imagerotate($img, 90, 0),
            default => $img,
        };

        return $rotated ?: $img;
    }
}
