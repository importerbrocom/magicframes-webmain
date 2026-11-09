<?php

namespace App\Policies;

class VideoPolicy extends PermissionPolicy
{
    protected function permission(): string
    {
        return 'manage videos';
    }
}
