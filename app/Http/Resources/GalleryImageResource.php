<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\GalleryImage */
class GalleryImageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'caption' => $this->description,
            'alt' => $this->alt_text ?: $this->title,
            'url' => $this->url,
            'thumb_url' => $this->thumb_url,
            'srcset' => $this->srcset,
            'width' => $this->width,
            'height' => $this->height,
            'is_featured' => $this->is_featured,
            'category' => $this->whenLoaded('category', fn () => [
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ]),
        ];
    }
}
