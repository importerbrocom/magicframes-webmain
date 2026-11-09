<?php

namespace App\Policies;

class NavigationMenuPolicy extends PermissionPolicy
{
    protected function permission(): string
    {
        return 'manage header';
    }
}
