<?php

namespace App\Services;

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
