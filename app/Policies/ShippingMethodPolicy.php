<?php

namespace App\Policies;

use App\Models\ShippingMethod;
use App\Models\User;

class ShippingMethodPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('shipping-methods.view');
    }

    public function view(User $user, ShippingMethod $shippingMethod): bool
    {
        return $user->can('shipping-methods.view');
    }

    public function create(User $user): bool
    {
        return $user->can('shipping-methods.create');
    }

    public function update(User $user, ShippingMethod $shippingMethod): bool
    {
        return $user->can('shipping-methods.update');
    }

    public function delete(User $user, ShippingMethod $shippingMethod): bool
    {
        return $user->can('shipping-methods.delete');
    }
}
