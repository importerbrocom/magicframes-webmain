<?php

namespace App\Observers;

use App\Services\ContentCache;

class ContentObserver
{
    public function saved(): void
    {
        ContentCache::bump();
    }

    public function deleted(): void
    {
        ContentCache::bump();
    }
}
