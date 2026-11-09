<?php

namespace App\Models;

use App\Observers\ContentObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy(ContentObserver::class)]
class NavigationMenu extends Model
{
    public const LOCATIONS = [
        'header_left' => 'Header – left of logo',
        'header_right' => 'Header – right of logo',
        'mobile_only' => 'Mobile menu only',
        'footer' => 'Footer links',
    ];

    protected $fillable = ['location', 'label', 'page_id', 'url', 'open_in_new_tab', 'sort_order', 'is_visible'];

    protected function casts(): array
    {
        return ['open_in_new_tab' => 'boolean', 'is_visible' => 'boolean', 'sort_order' => 'integer'];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true)->orderBy('sort_order')->orderBy('id');
    }

    public function getHrefAttribute(): string
    {
        if ($this->page) {
            return $this->page->path;
        }

        return $this->url ?: '#';
    }
}
