<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Base policy: every action on a resource requires one named permission.
 * Super admins bypass via Gate::before (see AppServiceProvider).
 */
abstract class PermissionPolicy
{
    abstract protected function permission(): string;

    public function viewAny(User $user): bool { return $user->can($this->permission()); }
    public function view(User $user, Model $model): bool { return $user->can($this->permission()); }
    public function create(User $user): bool { return $user->can($this->permission()); }
    public function update(User $user, Model $model): bool { return $user->can($this->permission()); }
    public function delete(User $user, Model $model): bool { return $user->can($this->permission()); }
    public function deleteAny(User $user): bool { return $user->can($this->permission()); }
    public function reorder(User $user): bool { return $user->can($this->permission()); }
}
