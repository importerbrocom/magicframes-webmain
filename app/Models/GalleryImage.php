<?php

namespace App\Models;

use App\Observers\ContentObserver;
use App\Services\ImageProcessor;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[ObservedBy(ContentObserver::class)]
class GalleryImage extends Model
{
    protected $fillable = [
        'gallery_category_id', 'title', 'description', 'alt_text', 'path', 'variants', 'width', 'height',
        'sort_order', 'is_featured', 'is_published',
    ];

    protected function casts(): array
    {
        return [
            'variants' => 'array',
            'is_featured' => 'boolean',
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // (Re)generate responsive variants whenever the file changes (create or replace).
        static::saving(function (self $image) {
            if ($image->isDirty('path') && $image->path) {
                if ($image->exists && $image->getOriginal('path')) {
                    app(ImageProcessor::class)->delete($image->getOriginal('path'), $image->getOriginal('variants'));
                }
                $result = app(ImageProcessor::class)->process($image->path);
                $image->width = $result['width'];
                $image->height = $result['height'];
                $image->variants = $result['variants'];
            }
        });

        static::deleting(function (self $image) {
            app(ImageProcessor::class)->delete($image->path, $image->variants);
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(GalleryCategory::class, 'gallery_category_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->whereHas('category', fn (Builder $q) => $q->where('is_published', true));
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->path);
    }

    public function getThumbUrlAttribute(): string
    {
        $variants = $this->variants ?? [];
        $path = $variants[480] ?? $variants['480'] ?? null;

        return $path ? Storage::disk('public')->url($path) : $this->url;
    }

    /** Best size for the lightbox: the 1600px variant when available, else the original. */
    public function getLargeUrlAttribute(): string
    {
        $variants = $this->variants ?? [];
        $path = $variants[1600] ?? $variants['1600'] ?? null;

        return $path ? Storage::disk('public')->url($path) : $this->url;
    }

    public function getSrcsetAttribute(): ?string
    {
        $parts = [];
        foreach ($this->variants ?? [] as $width => $path) {
            $parts[] = Storage::disk('public')->url($path).' '.$width.'w';
        }

        return $parts ? implode(', ', $parts) : null;
    }
}
