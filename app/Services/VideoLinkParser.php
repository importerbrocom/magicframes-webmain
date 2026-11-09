<?php

namespace App\Services;

class VideoLinkParser
{
    /**
     * @return array{type:string,id:string}|null  type is "youtube" or "vimeo"
     */
    public static function parse(?string $url): ?array
    {
        if (! $url) {
            return null;
        }
        $url = trim($url);

        if (preg_match('~^https?://(?:www\.|m\.)?(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/)|youtu\.be/)([A-Za-z0-9_-]{11})~i', $url, $m)) {
            return ['type' => 'youtube', 'id' => $m[1]];
        }

        if (preg_match('~^https?://(?:www\.)?(?:player\.)?vimeo\.com/(?:video/|channels/[^/]+/|groups/[^/]+/videos/)?(\d+)~i', $url, $m)) {
            return ['type' => 'vimeo', 'id' => $m[1]];
        }

        return null;
    }

    public static function embedUrl(string $type, string $id): string
    {
        return match ($type) {
            'youtube' => "https://www.youtube-nocookie.com/embed/{$id}?rel=0&modestbranding=1",
            'vimeo' => "https://player.vimeo.com/video/{$id}?dnt=1",
            default => '',
        };
    }

    public static function thumbnailUrl(string $type, string $id): ?string
    {
        return $type === 'youtube' ? "https://img.youtube.com/vi/{$id}/hqdefault.jpg" : null;
    }
}
