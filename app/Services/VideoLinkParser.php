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

    /** Google Drive share link -> file id (file must be shared as "Anyone with the link"). */
    public static function driveId(?string $url): ?string
    {
        if ($url && preg_match('~^https?://drive\.google\.com/(?:file/d/|open\?(?:.*&)?id=|uc\?(?:.*&)?id=)([A-Za-z0-9_-]{10,})~i', trim($url), $m)) {
            return $m[1];
        }

        return null;
    }

    /**
     * Hero-slide background embed for a pasted link.
     *
     * @return array{type:string,src:string}|null
     */
    public static function heroEmbed(?string $url): ?array
    {
        if ($v = self::parse($url)) {
            if ($v['type'] === 'youtube') {
                $id = $v['id'];

                return ['type' => 'youtube', 'src' => "https://www.youtube-nocookie.com/embed/{$id}?autoplay=1&mute=1&loop=1&playlist={$id}&controls=0&playsinline=1&rel=0&modestbranding=1&iv_load_policy=3&disablekb=1"];
            }
            if ($v['type'] === 'vimeo') {
                return ['type' => 'vimeo', 'src' => "https://player.vimeo.com/video/{$v['id']}?background=1&autoplay=1&muted=1&loop=1&dnt=1"];
            }
        }
        if ($id = self::driveId($url)) {
            return ['type' => 'drive', 'src' => "https://drive.google.com/file/d/{$id}/preview"];
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
