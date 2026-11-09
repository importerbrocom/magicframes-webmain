<?php

namespace App\Policies;

class HeroSlidePolicy extends PermissionPolicy
{
    protected function permission(): string
    {
        return 'manage homepage';
    }
}
