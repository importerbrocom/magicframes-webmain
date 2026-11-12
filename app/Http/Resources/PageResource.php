<?php

namespace App\Http\Resources;

use App\Models\Setting;
use App\Services\VideoLinkParser;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin \App\Models\Page */
class PageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $seo = $this->seo;
        $site = Setting::get('site.name', 'Magic Frames');
        $ogImage = $seo?->og_image_url
            ?? $this->banner_image_url
            ?? (Setting::get('seo.default_og_image') ? Storage::disk('public')->url(Setting::get('seo.default_og_image')) : null);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'path' => $this->path,
            'template' => $this->template,
            'banner' => [
                'label' => $this->banner_label,
                'title' => $this->banner_title ?: $this->title,
                'image_url' => $this->banner_image_url,
            ],
            'content' => $this->content,
            'sections' => $this->whenLoaded('sections', fn () => $this->sections->where('is_visible', true)->values()->map(function ($s) {
                $video = VideoLinkParser::parse($s->video_url);

                return [
                    'type' => $s->type,
                    'label' => $s->label,
                    'heading' => $s->heading,
                    'body' => $s->body,
                    'image_url' => $s->image_url,
                    'image_alt' => $s->image_alt ?: $s->heading,
                    'embed_url' => $video ? VideoLinkParser::embedUrl($video['type'], $video['id']) : null,
                    'button_label' => $s->button_label,
                    'button_url' => $s->button_url,
                    'image_right' => $s->image_right,
                    'members' => $s->type === 'team' ? collect($s->items ?? [])->filter(fn ($m) => filled($m['name'] ?? null))->map(fn ($m) => [
                        'name' => $m['name'], 'position' => $m['position'] ?? null,
                        'photo_url' => ! empty($m['photo']) ? \Illuminate\Support\Facades\Storage::disk('public')->url($m['photo']) : null,
                    ])->values()->all() : [],
                ];
            })),
            'seo' => [
                'title' => ($seo?->meta_title ?: $this->title).' — '.$site,
                'description' => $seo?->meta_description ?: Setting::get('seo.default_description'),
                'og_image' => $ogImage,
                'canonical' => $seo?->canonical_url,
                'noindex' => (bool) $seo?->noindex,
            ],
        ];
    }
}
