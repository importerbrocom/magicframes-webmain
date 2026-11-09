<?php

namespace App\Models;

use App\Observers\ContentObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[ObservedBy(ContentObserver::class)]
class VideoCategory extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'sort_order', 'is_published'];

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
        });
    }

    public function videos(): HasMany
    {
        return $this->hasMany(Video::class)->orderBy('sort_order')->orderByDesc('id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }
}
