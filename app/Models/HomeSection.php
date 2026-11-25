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

    /** key => [admin label, small label, title, body, visible by default] */
    public const DEFAULTS = [
        'hero' => ['Hero banner', null, null, null, true],
        'about' => ['About Magic Frames', 'Our Story', 'We turn moments into memories', null, true],
        'quote' => ['Quote band', null, null, null, false],
        'services' => ['Services showcase', 'What We Offer', 'Our Services', null, false],
        'stats' => ['Statistics & achievements', null, null, null, false],
        'portfolio' => ['Featured photography', null, null, null, false],   // off and without heading (client request)
        'videos' => ['Featured films', 'In Motion', 'Featured Films', null, false],
        'recent' => ['Recent projects', 'Latest Work', 'Recent Projects', null, false],
        'testimonials' => ['Client testimonials', 'Kind Words', 'Love Notes', null, false],
        'social' => ['Instagram / social', 'Follow Along', 'On Instagram', null, false],
        'contact' => ['Contact & enquiry', 'Get in Touch', "Let's plan something beautiful", "Tell us about your day and we'll be in touch within 24 hours.", true],
    ];

    /** Re-create deleted blocks (switched off, at the end of the list). Returns how many came back. */
    public static function restoreMissing(): int
    {
        $existing = static::pluck('key')->all();
        $order = (int) static::max('sort_order');
        $n = 0;
        foreach (self::DEFAULTS as $key => [$admin, $label, $title, $body]) {
            if (! in_array($key, $existing, true)) {
                static::create(['key' => $key, 'admin_label' => $admin, 'label' => $label, 'title' => $title, 'body' => $body,
                    'sort_order' => ++$order, 'is_visible' => false]);
                $n++;
            }
        }

        return $n;
    }

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
