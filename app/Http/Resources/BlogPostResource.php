<?php

namespace App\Http\Resources;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\BlogPost */
class BlogPostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $full = $request->is('api/v1/blog/*') || $request->attributes->get('blog_full', false);
        $seo = $this->seo;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'category' => $this->category,
            'excerpt' => $this->excerpt ?: str(strip_tags((string) $this->content))->limit(160)->toString(),
            'cover_url' => $this->cover_url,
            'cover_alt' => $this->cover_alt ?: $this->title,
            'author' => $this->author,
            'published_at' => $this->published_at?->toIso8601String(),
            'published_label' => $this->published_at?->format('M d, Y'),
            'reading_minutes' => $this->reading_minutes,
            'content' => $this->when($full, $this->content),
            'seo' => $this->when($full, fn () => [
                'title' => ($seo?->meta_title ?: $this->title).' — '.Setting::get('site.name', 'Magic Frames'),
                'description' => $seo?->meta_description ?: ($this->excerpt ?: str(strip_tags((string) $this->content))->limit(160)->toString()),
                'og_image' => $seo?->og_image_url ?: $this->cover_url,
                'noindex' => (bool) $seo?->noindex,
            ]),
        ];
    }
}
