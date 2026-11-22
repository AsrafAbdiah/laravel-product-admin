<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use App\Support\CurrentTeam;
use App\Support\PlanLimits;
use Illuminate\Auth\Access\Response;

/**
 * Who may do what with products, based on the permissions of the user's
 * role in the current team (see App\Support\Roles). Which team a product
 * belongs to is already enforced by the team scope, so these checks only
 * need to look at permissions.
 */
class ProductPolicy
{
    public function __construct(private PlanLimits $limits, private CurrentTeam $currentTeam) {}

    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('products.view');
    }

    public function view(User $user, Product $product): bool
    {
        return $user->checkPermissionTo('products.view');
    }

    /**
     * The role must allow it AND the shop's plan must have room for another product.
     */
    public function create(User $user): Response
    {
        if (! $user->checkPermissionTo('products.create')) {
            return Response::deny();
        }

        return $this->withinProductLimit();
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

    /**
     * Restoring brings a product back, so it counts against the plan too.
     */
    public function restore(User $user, Product $product): Response
    {
        if (! $user->checkPermissionTo('trash.manage')) {
            return Response::deny();
        }

        return $this->withinProductLimit();
    }

    public function forceDelete(User $user, Product $product): bool
    {
        return $user->checkPermissionTo('trash.manage');
    }

    public function import(User $user): Response
    {
        if (! $user->checkPermissionTo('products.import')) {
            return Response::deny();
        }

        return $this->limits->allowsImport($this->currentTeam->get())
            ? Response::allow()
            : Response::deny('Importing from a file is part of the Pro plan. The shop owner can upgrade on the Team page.');
    }

    public function export(User $user): bool
    {
        return $user->checkPermissionTo('products.export');
    }

    private function withinProductLimit(): Response
    {
        $team = $this->currentTeam->get();

        if ($this->limits->canAddProducts($team)) {
            return Response::allow();
        }

        $max = $this->limits->plan($team)['max_products'];

        return Response::deny("Your {$this->limits->plan($team)['name']} plan allows up to {$max} products. The shop owner can upgrade to Pro on the Team page.");
    }
}
