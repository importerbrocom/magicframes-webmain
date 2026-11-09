<?php

namespace App\Policies;

class GalleryImagePolicy extends PermissionPolicy
{
    protected function permission(): string
    {
        return 'manage gallery';
    }
}
