<?php

namespace App\Models;

use App\Observers\ContentObserver;
use App\Services\VideoLinkParser;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[ObservedBy(ContentObserver::class)]
class Video extends Model
{
    public const SOURCE_UPLOAD = 'upload';
    public const SOURCE_YOUTUBE = 'youtube';
    public const SOURCE_VIMEO = 'vimeo';

    protected $fillable = [
        'video_category_id', 'title', 'slug', 'description', 'source_type', 'video_url', 'external_id',
        'video_path', 'thumbnail_path', 'duration_seconds', 'sort_order', 'is_featured', 'is_published',
    ];

    protected function casts(): array
    {
        return ['is_featured' => 'boolean', 'is_published' => 'boolean', 'sort_order' => 'integer'];
    }

    protected static function booted(): void
    {
        static::saving(function (self $video) {
            if (blank($video->slug)) {
                $base = Str::slug($video->title) ?: 'video';
                $slug = $base;
                $i = 2;
                while (static::where('slug', $slug)->where('id', '!=', $video->id ?? 0)->exists()) {
                    $slug = $base.'-'.$i++;
                }
                $video->slug = $slug;
            }

            // Derive source type + external id from the pasted link; clear unused fields.
            if ($video->source_type === self::SOURCE_UPLOAD) {
                $video->video_url = null;
                $video->external_id = null;
            } else {
                $parsed = VideoLinkParser::parse($video->video_url);
                if ($parsed) {
                    $video->source_type = $parsed['type'];
                    $video->external_id = $parsed['id'];
                }
                $video->video_path = null;
            }

            foreach (['video_path', 'thumbnail_path'] as $column) {
                $old = $video->getOriginal($column);
                if ($video->exists && $old && $video->isDirty($column)) {
                    Storage::disk('public')->delete($old);
                }
            }
        });

        static::deleting(function (self $video) {
            foreach ([$video->video_path, $video->thumbnail_path] as $path) {
                if ($path) {
                    Storage::disk('public')->delete($path);
                }
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(VideoCategory::class, 'video_category_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        if ($this->thumbnail_path) {
            return Storage::disk('public')->url($this->thumbnail_path);
        }

        return $this->external_id ? VideoLinkParser::thumbnailUrl($this->source_type, $this->external_id) : null;
    }

    /** URL for an iframe (external) — null for uploaded files. */
    public function getEmbedUrlAttribute(): ?string
    {
        return $this->source_type !== self::SOURCE_UPLOAD && $this->external_id
            ? VideoLinkParser::embedUrl($this->source_type, $this->external_id)
            : null;
    }

    /** URL for a <video> tag (uploaded) — null for external sources. */
    public function getFileUrlAttribute(): ?string
    {
        return $this->video_path ? Storage::disk('public')->url($this->video_path) : null;
    }
}
