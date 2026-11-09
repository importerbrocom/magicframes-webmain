<?php

namespace App\Models;

use App\Models\Concerns\CleansUpFiles;
use App\Observers\ContentObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy(ContentObserver::class)]
class Testimonial extends Model
{
    use CleansUpFiles;

    protected array $fileColumns = ['photo_path'];

    protected $fillable = ['client_name', 'client_role', 'photo_path', 'rating', 'body', 'sort_order', 'is_published'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean', 'sort_order' => 'integer', 'rating' => 'integer'];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)->orderBy('sort_order')->orderBy('id');
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->publicUrl($this->photo_path);
    }
}
