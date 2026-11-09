<?php

namespace App\Policies;

class BlogPostPolicy extends PermissionPolicy
{
    protected function permission(): string
    {
        return 'manage blog';
    }
}
