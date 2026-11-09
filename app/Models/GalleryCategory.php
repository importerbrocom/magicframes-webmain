<?php

namespace App\Models;

use App\Observers\ContentObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[ObservedBy(ContentObserver::class)]
class GalleryCategory extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'cover_path', 'meta_title', 'meta_description', 'sort_order', 'is_published',
    ];

    protected function casts(): array
    {
        return ['is_published' => 'boolean', 'sort_order' => 'integer'];
    }

    protected static function booted(): void
    {
        static::saving(function (self $category) {
            if (blank($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
            if ($category->isDirty('cover_path') && $category->getOriginal('cover_path')) {
                Storage::disk('public')->delete($category->getOriginal('cover_path'));
            }
        });

        static::deleting(function (self $category) {
            // Delete images one by one so their files are removed too.
            $category->images()->each(fn (GalleryImage $image) => $image->delete());
            if ($category->cover_path) {
                Storage::disk('public')->delete($category->cover_path);
            }
        });
    }

    public function images(): HasMany
    {
        return $this->hasMany(GalleryImage::class)->orderBy('sort_order')->orderBy('id');
    }

    public function publishedImages(): HasMany
    {
        return $this->images()->where('is_published', true);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function getCoverUrlAttribute(): ?string
    {
        if ($this->cover_path) {
            return Storage::disk('public')->url($this->cover_path);
        }

        // Fall back to the first published image of the category.
        return $this->publishedImages()->first()?->url;
    }
}
