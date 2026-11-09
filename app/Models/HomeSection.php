<?php

namespace App\Models;

use App\Models\Concerns\CleansUpFiles;
use App\Observers\ContentObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** One row per block of the home page; order and visibility are controlled from the admin. */
#[ObservedBy(ContentObserver::class)]
class HomeSection extends Model
{
    use CleansUpFiles;

    protected array $fileColumns = ['image_path', 'image_secondary_path'];

    protected $fillable = [
        'key', 'admin_label', 'label', 'title', 'body', 'body_secondary', 'image_path', 'image_secondary_path',
        'button_label', 'button_url', 'items_limit', 'sort_order', 'is_visible',
    ];

    protected function casts(): array
    {
        return ['is_visible' => 'boolean', 'sort_order' => 'integer', 'items_limit' => 'integer'];
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true)->orderBy('sort_order')->orderBy('id');
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->publicUrl($this->image_path);
    }

    public function getImageSecondaryUrlAttribute(): ?string
    {
        return $this->publicUrl($this->image_secondary_path);
    }
}
