<?php

namespace App\Models;

use App\Observers\ContentObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[ObservedBy(ContentObserver::class)]
class PageSection extends Model
{
    public const TYPES = [
        'text' => 'Text',
        'image_text' => 'Image + text',
        'image' => 'Full-width image',
        'video' => 'Video (YouTube / Vimeo)',
        'cta' => 'Call to action',
    ];

    protected $fillable = [
        'page_id', 'type', 'label', 'heading', 'body', 'image_path', 'image_alt', 'video_url',
        'button_label', 'button_url', 'image_right', 'sort_order', 'is_visible',
    ];

    protected function casts(): array
    {
        return ['image_right' => 'boolean', 'is_visible' => 'boolean', 'sort_order' => 'integer'];
    }

    protected static function booted(): void
    {
        static::saving(function (self $s) {
            if ($s->exists && $s->isDirty('image_path') && $s->getOriginal('image_path')) {
                Storage::disk('public')->delete($s->getOriginal('image_path'));
            }
        });
        static::deleting(function (self $s) {
            if ($s->image_path) {
                Storage::disk('public')->delete($s->image_path);
            }
        });
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }
}
