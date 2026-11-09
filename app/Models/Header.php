<?php

namespace App\Models;

use App\Observers\ContentObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

#[ObservedBy(ContentObserver::class)]
class Header extends Model
{
    protected $fillable = [
        'logo_path', 'logo_alt', 'is_visible', 'is_sticky', 'background_style', 'show_cta', 'cta_label', 'cta_url',
        'show_floating_button', 'show_social_in_mobile_menu',
    ];

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean', 'is_sticky' => 'boolean', 'show_cta' => 'boolean',
            'show_floating_button' => 'boolean', 'show_social_in_mobile_menu' => 'boolean',
        ];
    }

    public static function current(): self
    {
        return static::query()->first() ?? static::create([]);
    }

    public function getLogoUrlAttribute(): string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : asset('images/logo.png');
    }
}
