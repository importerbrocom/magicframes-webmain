<?php

namespace App\Models;

use App\Models\Concerns\HasSeo;
use App\Observers\ContentObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[ObservedBy(ContentObserver::class)]
class Page extends Model
{
    use HasSeo;

    public const TEMPLATES = [
        'default' => 'Standard page',
        'home' => 'Home',
        'about' => 'About',
        'services' => 'Services',
        'portfolio' => 'Portfolio',
        'gallery' => 'Photo gallery',
        'videos' => 'Video gallery',
        'blog' => 'Blog',
        'contact' => 'Contact',
        'shop' => 'Shop',
    ];

    protected $fillable = [
        'title', 'slug', 'template', 'is_system', 'banner_label', 'banner_title', 'banner_image_path', 'content', 'is_published',
    ];

    protected function casts(): array
    {
        return ['is_system' => 'boolean', 'is_published' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saving(function (self $page) {
            if (blank($page->slug) && $page->template !== 'home') {
                $page->slug = Str::slug($page->title);
            }
            if ($page->isDirty('banner_image_path') && $page->getOriginal('banner_image_path')) {
                Storage::disk('public')->delete($page->getOriginal('banner_image_path'));
            }
        });

        static::deleting(function (self $page) {
            abort_if($page->is_system, 403, 'System pages cannot be deleted.');
            $page->sections()->each->delete();
            if ($page->banner_image_path) {
                Storage::disk('public')->delete($page->banner_image_path);
            }
        });
    }

    public function sections(): HasMany
    {
        return $this->hasMany(PageSection::class)->orderBy('sort_order')->orderBy('id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /** Public URL path. The home page uses slug "home" and maps to "/". */
    public function getPathAttribute(): string
    {
        return $this->template === 'home' ? '/' : '/'.$this->slug;
    }

    public function getBannerImageUrlAttribute(): ?string
    {
        return $this->banner_image_path ? Storage::disk('public')->url($this->banner_image_path) : null;
    }
}
