<?php

namespace App\Policies;

class StatisticPolicy extends PermissionPolicy
{
    protected function permission(): string
    {
        return 'manage homepage';
    }
}
