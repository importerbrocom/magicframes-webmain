<?php

namespace App\Models;

use App\Models\Concerns\CleansUpFiles;
use App\Models\Concerns\HasSeo;
use App\Observers\ContentObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[ObservedBy(ContentObserver::class)]
class BlogPost extends Model
{
    use CleansUpFiles, HasSeo;

    protected array $fileColumns = ['cover_path'];

    protected $fillable = [
        'title', 'slug', 'category', 'excerpt', 'content', 'cover_path', 'cover_alt', 'author', 'published_at', 'is_published',
    ];

    protected function casts(): array
    {
        return ['is_published' => 'boolean', 'published_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::saving(function (self $post) {
            if (blank($post->slug)) {
                $base = Str::slug($post->title) ?: 'post';
                $slug = $base;
                $i = 2;
                while (static::where('slug', $slug)->where('id', '!=', $post->id ?? 0)->exists()) {
                    $slug = $base.'-'.$i++;
                }
                $post->slug = $slug;
            }
            // Publishing without a date means "now".
            if ($post->is_published && ! $post->published_at) {
                $post->published_at = now();
            }
        });
    }

    /** Live posts: switched on and their publish date has arrived (scheduling supported). */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)->where('published_at', '<=', now());
    }

    public function getCoverUrlAttribute(): ?string
    {
        return $this->publicUrl($this->cover_path);
    }

    public function getReadingMinutesAttribute(): int
    {
        return max(1, (int) ceil(str_word_count(strip_tags((string) $this->content)) / 200));
    }
}
