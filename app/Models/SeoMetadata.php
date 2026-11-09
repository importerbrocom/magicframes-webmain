<?php

namespace App\Models;

use App\Observers\ContentObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

#[ObservedBy(ContentObserver::class)]
class SeoMetadata extends Model
{
    protected $table = 'seo_metadata';

    protected $fillable = ['meta_title', 'meta_description', 'og_image_path', 'canonical_url', 'noindex'];

    protected function casts(): array
    {
        return ['noindex' => 'boolean'];
    }

    public function seoable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getOgImageUrlAttribute(): ?string
    {
        return $this->og_image_path ? Storage::disk('public')->url($this->og_image_path) : null;
    }
}
