<?php

namespace App\Policies;

class ServicePolicy extends PermissionPolicy
{
    protected function permission(): string
    {
        return 'manage services';
    }
}
