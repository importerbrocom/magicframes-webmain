<?php

namespace App\Policies;

class GalleryCategoryPolicy extends PermissionPolicy
{
    protected function permission(): string
    {
        return 'manage gallery';
    }
}
