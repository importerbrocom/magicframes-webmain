<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

/**
 * Lazily creates width-limited WebP copies of uploaded images (hero, banners, cards…),
 * cached under storage/app/public/_cache/{width}/. Falls back to the original on any problem.
 */
class ImageResizer
{
    public static function url(?string $publicUrl, int $width): ?string
    {
        if (! $publicUrl) {
            return null;
        }
        $path = self::pathFromUrl($publicUrl);
        if ($path === null) {
            return $publicUrl;
        }

        $disk = Storage::disk('public');
        $target = '_cache/hd/'.$width.'/'.preg_replace('/\.[A-Za-z0-9]+$/', '', $path).'.webp';

        try {
            if (! $disk->exists($target)) {
                if (! $disk->exists($path) || ! function_exists('imagewebp')) {
                    return $publicUrl;
                }
                $raw = $disk->get($path);
                $size = @getimagesizefromstring($raw);
                if (! $size || ! ImageProcessor::allowMemoryFor($size[0], $size[1])) {
                    return $publicUrl;
                }
                $src = @imagecreatefromstring($raw);
                unset($raw);
                if (! $src) {
                    return $publicUrl;
                }
                $w = imagesx($src);
                $h = imagesy($src);
                $newW = min($width, $w);
                $newH = (int) round($h * ($newW / $w));
                $dst = imagecreatetruecolor($newW, $newH);
                imagealphablending($dst, false);
                imagesavealpha($dst, true);
                imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $w, $h);
                ob_start();
                imagewebp($dst, null, ImageProcessor::QUALITY);
                $disk->put($target, ob_get_clean());
                imagedestroy($src);
                imagedestroy($dst);
            }
        } catch (\Throwable) {
            return $publicUrl;
        }

        return $disk->url($target);
    }

    /** Public-disk path for a URL that points at our /storage, otherwise null (external/static assets). */
    private static function pathFromUrl(string $url): ?string
    {
        $base = rtrim(Storage::disk('public')->url(''), '/').'/';
        if (str_starts_with($url, $base)) {
            $path = substr($url, strlen($base));
        } elseif (preg_match('~^(?:https?://[^/]+)?/storage/(.+)$~', $url, $m)) {
            $path = $m[1];
        } else {
            return null;
        }
        $path = urldecode(strtok($path, '?'));

        return str_contains($path, '..') || str_starts_with($path, '_cache/') ? null : $path;
    }
}
