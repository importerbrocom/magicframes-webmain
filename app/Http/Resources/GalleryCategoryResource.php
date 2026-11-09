<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\GalleryCategory */
class GalleryCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'cover_url' => $this->cover_url,
            'images_count' => $this->published_images_count ?? null,
            'meta_title' => $this->meta_title ?: $this->name,
            'meta_description' => $this->meta_description ?: $this->description,
        ];
    }
}
