<?php

namespace App\Models;

use App\Models\Concerns\CleansUpFiles;
use App\Observers\ContentObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[ObservedBy(ContentObserver::class)]
class Service extends Model
{
    use CleansUpFiles;

    protected array $fileColumns = ['image_path'];

    protected $fillable = ['title', 'slug', 'icon', 'short_description', 'description', 'image_path', 'gallery', 'videos', 'highlights', 'faqs', 'seo_title', 'seo_description', 'sort_order', 'is_published'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean', 'sort_order' => 'integer', 'gallery' => 'array', 'videos' => 'array', 'highlights' => 'array', 'faqs' => 'array'];
    }

    protected static function booted(): void
    {
        // Remove uploaded photos / videos that were taken out of the lists, or when the service is deleted.
        static::saving(function (self $s) {
            if (! $s->exists) {
                return;
            }
            $before = $s->detailFiles(
                json_decode((string) ($s->getRawOriginal('gallery') ?? '[]'), true) ?: [],
                json_decode((string) ($s->getRawOriginal('videos') ?? '[]'), true) ?: [],
            );
            foreach (array_diff($before, $s->detailFiles()) as $gone) {
                Storage::disk('public')->delete($gone);
            }
        });
        static::deleting(function (self $s) {
            foreach ($s->detailFiles() as $f) {
                Storage::disk('public')->delete($f);
            }
        });

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

    /** Files used by the photo / video lists (public-disk paths). */
    public function detailFiles(?array $gallery = null, ?array $videos = null): array
    {
        $files = [];
        foreach ($gallery ?? $this->gallery ?? [] as $g) {
            $files[] = $g['image'] ?? null;
        }
        foreach ($videos ?? $this->videos ?? [] as $v) {
            $files[] = $v['file'] ?? null;
            $files[] = $v['thumbnail'] ?? null;
        }

        return array_values(array_unique(array_filter($files)));
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)->orderBy('sort_order')->orderBy('id');
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->publicUrl($this->image_path);
    }

    public function getUrlAttribute(): string
    {
        return '/services/'.$this->slug;
    }
}
