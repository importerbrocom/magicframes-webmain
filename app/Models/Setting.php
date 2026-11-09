<?php

namespace App\Models;

use App\Observers\ContentObserver;
use App\Services\ContentCache;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;

/** Key/value store for editable site-wide content (contact info, social links, footer...). */
#[ObservedBy(ContentObserver::class)]
class Setting extends Model
{
    protected $table = 'website_settings';

    protected $fillable = ['key', 'value'];

    /** @return array<string,?string> */
    public static function allValues(): array
    {
        return ContentCache::remember('settings:all', fn () => static::query()->pluck('value', 'key')->all(), 86400);
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $value = static::allValues()[$key] ?? null;

        return filled($value) ? $value : $default;
    }

    /** @param array<string,mixed> $values */
    public static function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            static::updateOrCreate(['key' => $key], ['value' => filled($value) ? (string) $value : null]);
        }
    }
}
