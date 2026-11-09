<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PagePolicy extends PermissionPolicy
{
    protected function permission(): string
    {
        return 'manage pages';
    }

    /** Built-in pages (home, about, contact…) can be edited but never deleted. */
    public function delete(User $user, Model $model): bool
    {
        return $user->can('manage pages') && ! $model->is_system;
    }
}
