<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class UserPolicy extends PermissionPolicy
{
    protected function permission(): string
    {
        return 'manage users';
    }

    /** Users may never delete themselves. */
    public function delete(User $user, Model $model): bool
    {
        return $user->can('manage users') && $user->isNot($model);
    }
}
