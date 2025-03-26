<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Product;

class ProductPolicy
{
    /**
     * Determine if the user can view any products.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(User::PERMISSION_READ);
    }

    /**
     * Determine if the user can view the product.
     */
    public function view(User $user): bool
    {
        return $user->hasPermission(User::PERMISSION_READ);
    }

    /**
     * Determine if the user can create products.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission(User::PERMISSION_CREATE);
    }

    /**
     * Determine if the user can update the product.
     */
    public function update(User $user): bool
    {
        return $user->hasPermission(User::PERMISSION_EDIT);
    }

    /**
     * Determine if the user can delete the product.
     */
    public function delete(User $user): bool
    {
        return $user->hasPermission(User::PERMISSION_DELETE);
    }
} 