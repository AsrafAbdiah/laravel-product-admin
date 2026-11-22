<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

/**
 * Who may do what with products, based on the permissions of the user's
 * role in the current team (see App\Support\Roles). Which team a product
 * belongs to is already enforced by the team scope, so these checks only
 * need to look at permissions.
 */
class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('products.view');
    }

    public function view(User $user, Product $product): bool
    {
        return $user->checkPermissionTo('products.view');
    }

    public function create(User $user): bool
    {
        return $user->checkPermissionTo('products.create');
    }

    public function update(User $user, Product $product): bool
    {
        return $user->checkPermissionTo('products.update');
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->checkPermissionTo('products.delete');
    }

    /**
     * Deleting several products at once.
     */
    public function deleteAny(User $user): bool
    {
        return $user->checkPermissionTo('products.delete');
    }

    public function viewTrash(User $user): bool
    {
        return $user->checkPermissionTo('trash.manage');
    }

    public function restore(User $user, Product $product): bool
    {
        return $user->checkPermissionTo('trash.manage');
    }

    public function forceDelete(User $user, Product $product): bool
    {
        return $user->checkPermissionTo('trash.manage');
    }

    public function import(User $user): bool
    {
        return $user->checkPermissionTo('products.import');
    }

    public function export(User $user): bool
    {
        return $user->checkPermissionTo('products.export');
    }
}
