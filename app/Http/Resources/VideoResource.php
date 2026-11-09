<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Video */
class VideoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'source' => $this->source_type,
            // iframe URL for youtube/vimeo, null for uploads
            'embed_url' => $this->embed_url,
            // <video src> for uploads, null for external
            'file_url' => $this->file_url,
            'thumbnail_url' => $this->thumbnail_url,
            'is_featured' => $this->is_featured,
            'category' => $this->whenLoaded('category', fn () => $this->category ? [
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ] : null),
        ];
    }
}
