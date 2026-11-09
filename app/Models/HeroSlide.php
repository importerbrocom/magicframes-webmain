<?php

namespace App\Models;

use App\Models\Concerns\CleansUpFiles;
use App\Observers\ContentObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy(ContentObserver::class)]
class HeroSlide extends Model
{
    use CleansUpFiles;

    protected array $fileColumns = ['image_path'];

    protected $fillable = ['heading', 'subheading', 'description', 'image_path', 'button_label', 'button_url', 'sort_order', 'is_published'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean', 'sort_order' => 'integer'];
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
