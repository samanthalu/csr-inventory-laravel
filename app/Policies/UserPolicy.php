<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserRoles;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    // public function viewAny(User $user): bool
    // {
    //     //
    // }

    /**
     * Determine whether the user can view the model.
     */
    // public function view(User $user, User $model): bool
    // {
    //     //
    // }

    /**
     * Determine whether the user can create models.
      */
    // public function create(User $user): bool
    // {
    //     //
    //     // $roles = UserRoles::firstWhere('role_user_id', $user->id);
    //     // return $roles->role_user_type === 'admin' || $roles->role_write;
    // }

    /**
     * Determine whether the user can update the model.
     */
    // public function update(User $user): bool
    // {
    //     //
    //     // $roles = UserRoles::firstWhere('role_user_id', $user->id);
    //     // \Log::info($roles);
    //     // \Log::info($roles->role_user_type === 'admin' || $roles->role_edit);
    //     // return $roles->role_user_type === 'admin' || $roles->role_edit;
    //     // return true;
    // }

    /**
     * Determine whether the user can delete the model.
     */
    // public function delete(User $user, User $model): bool
    // {
    //     //
    // }

    /**
     * Determine whether the user can restore the model.
     */
    // public function restore(User $user, User $model): bool
    // {
    //     //
    // }

    /**
     * Determine whether the user can permanently delete the model.
     */
    // public function forceDelete(User $user, User $model): bool
    // {
    //     //
    // }
}
