<?php

namespace App\Policies;

class VideoCategoryPolicy extends PermissionPolicy
{
    protected function permission(): string
    {
        return 'manage videos';
    }
}
