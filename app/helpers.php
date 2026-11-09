<?php

use App\Services\ImageResizer;

if (! function_exists('img_url')) {
    /** Width-limited WebP URL for an uploaded image URL (see ImageResizer). */
    function img_url(?string $url, int $width = 1200): ?string
    {
        return ImageResizer::url($url, $width);
    }
}

if (! function_exists('rich')) {
    /** Render admin rich-text safely (Filament/TipTap parser drops anything unknown). */
    function rich(?string $html): string
    {
        return filled($html)
            ? \Filament\Forms\Components\RichEditor\RichContentRenderer::make($html)->toHtml()
            : '';
    }
}

if (! function_exists('paragraphs')) {
    /** Plain text → escaped <p> paragraphs (blank line separated). */
    function paragraphs(?string $text): string
    {
        if (blank($text)) {
            return '';
        }

        return collect(preg_split('/\R{2,}/', trim($text)))
            ->map(fn ($p) => '<p>'.nl2br(e(trim($p))).'</p>')->implode("\n");
    }
}

if (! function_exists('nav_active')) {
    function nav_active(string $url): bool
    {
        $path = '/'.ltrim((string) parse_url($url, PHP_URL_PATH), '/');
        $current = '/'.ltrim(request()->path(), '/');

        return $path === '/' ? $current === '/' : ($current === $path || str_starts_with($current, $path.'/'));
    }
}
