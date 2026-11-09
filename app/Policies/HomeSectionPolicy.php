<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class HomeSectionPolicy extends PermissionPolicy
{
    protected function permission(): string
    {
        return 'manage homepage';
    }

    public function create(User $user): bool { return false; }

    public function delete(User $user, Model $model): bool { return false; }

    public function deleteAny(User $user): bool { return false; }
}
