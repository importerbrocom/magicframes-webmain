<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Storage;

/**
 * Models using this trait declare `protected array $fileColumns` (public-disk paths).
 * Replaced or deleted files are removed from storage automatically.
 */
trait CleansUpFiles
{
    protected static function bootCleansUpFiles(): void
    {
        static::saving(function ($model) {
            if (! $model->exists) {
                return;
            }
            foreach ($model->fileColumns as $column) {
                $old = $model->getOriginal($column);
                if ($old && $model->isDirty($column)) {
                    Storage::disk('public')->delete($old);
                }
            }
        });

        static::deleting(function ($model) {
            foreach ($model->fileColumns as $column) {
                if ($model->{$column}) {
                    Storage::disk('public')->delete($model->{$column});
                }
            }
        });
    }

    protected function publicUrl(?string $path): ?string
    {
        return $path ? Storage::disk('public')->url($path) : null;
    }
}
