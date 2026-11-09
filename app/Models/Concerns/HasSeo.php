<?php

namespace App\Models\Concerns;

use App\Models\SeoMetadata;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasSeo
{
    public function seo(): MorphOne
    {
        return $this->morphOne(SeoMetadata::class, 'seoable');
    }

    protected static function bootHasSeo(): void
    {
        // Must return nothing: a non-null return value would halt other `deleting` listeners.
        static::deleting(function ($model): void {
            $model->seo()->delete();
        });
    }
}
