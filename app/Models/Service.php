<?php

namespace App\Models;

use App\Models\Concerns\CleansUpFiles;
use App\Observers\ContentObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[ObservedBy(ContentObserver::class)]
class Service extends Model
{
    use CleansUpFiles;

    protected array $fileColumns = ['image_path'];

    protected $fillable = ['title', 'slug', 'icon', 'short_description', 'description', 'image_path', 'sort_order', 'is_published'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean', 'sort_order' => 'integer'];
    }

    protected static function booted(): void
    {
        static::saving(function (self $s) {
            if (blank($s->slug)) {
                $base = Str::slug($s->title) ?: 'service';
                $slug = $base;
                $i = 2;
                while (static::where('slug', $slug)->where('id', '!=', $s->id ?? 0)->exists()) {
                    $slug = $base.'-'.$i++;
                }
                $s->slug = $slug;
            }
        });
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)->orderBy('sort_order')->orderBy('id');
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->publicUrl($this->image_path);
    }
}
